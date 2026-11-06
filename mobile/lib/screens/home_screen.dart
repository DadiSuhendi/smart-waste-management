import 'package:flutter/material.dart';

import '../core/models.dart';
import '../core/session.dart';
import '../widgets/common.dart';
import 'report_form_screen.dart';
import 'report_detail_screen.dart';

class HomeScreen extends StatefulWidget {
  final Session session;
  const HomeScreen({required this.session, super.key});
  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  int tab = 0, page = 1, requestId = 0;
  String filter = '', query = '';
  bool loading = true, historyLoading = false, loggingOut = false;
  String? error, historyError;
  Map<String, dynamic> stats = {};
  List<WasteReport> recent = [];
  ReportPage? history;
  final search = TextEditingController();
  @override
  void initState() {
    super.initState();
    refresh();
    loadHistory();
  }

  @override
  void dispose() {
    search.dispose();
    super.dispose();
  }

  Future<void> refresh() async {
    if (mounted) {
      setState(() {
        loading = true;
        error = null;
      });
    }
    try {
      final results = await Future.wait([
        widget.session.api.request('GET', '/dashboard'),
        widget.session.api.reports(),
      ]);
      if (!mounted) return;
      setState(() {
        stats = (results[0] as Map<String, dynamic>)['data']['stats'];
        recent = (results[1] as ReportPage).reports.take(4).toList();
      });
    } catch (e) {
      if (mounted) setState(() => error = e.toString());
    } finally {
      if (mounted) setState(() => loading = false);
    }
  }

  Future<void> loadHistory() async {
    final id = ++requestId;
    setState(() {
      historyLoading = true;
      historyError = null;
    });
    try {
      final result = await widget.session.api.reports(
        page: page,
        status: filter,
        query: query,
      );
      if (mounted && id == requestId) setState(() => history = result);
    } catch (e) {
      if (mounted && id == requestId) {
        setState(() => historyError = e.toString());
      }
    } finally {
      if (mounted && id == requestId) setState(() => historyLoading = false);
    }
  }

  Future<void> createReport() async {
    final created = await Navigator.of(context).push<bool>(
      MaterialPageRoute(
        builder: (_) => ReportFormScreen(api: widget.session.api),
      ),
    );
    if (created == true && mounted) {
      page = 1;
      await Future.wait([refresh(), loadHistory()]);
    }
  }

  Future<void> openReport(WasteReport report) async {
    await Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) =>
            ReportDetailScreen(api: widget.session.api, id: report.id),
      ),
    );
    if (mounted) {
      await Future.wait([refresh(), loadHistory()]);
    }
  }

  Future<void> logout() async {
    setState(() => loggingOut = true);
    try {
      await widget.session.logout();
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context)
            .showSnackBar(SnackBar(content: Text(e.toString())));
      }
    } finally {
      if (mounted) setState(() => loggingOut = false);
    }
  }

  void showHistory({String status = ''}) {
    setState(() {
      tab = 1;
      filter = status;
      page = 1;
      query = '';
      search.clear();
    });
    loadHistory();
  }

  Widget tile(WasteReport report) => ReportTile(
    report: report,
    headers: widget.session.api.headers,
    onTap: () => openReport(report),
  );
  @override
  Widget build(BuildContext context) => Scaffold(
    appBar: AppBar(
      title: const Brand(),
      actions: [
        Padding(
          padding: const EdgeInsets.only(right: 20),
          child: CircleAvatar(
            backgroundColor: const Color(0xffe5ead5),
            child: Text(
              widget.session.user!.name.substring(0, 1).toUpperCase(),
              style: const TextStyle(color: green),
            ),
          ),
        ),
      ],
    ),
    body: SafeArea(
      child: Center(
        child: ConstrainedBox(
          constraints: const BoxConstraints(maxWidth: 680),
          child: tab == 0
              ? dashboard()
              : tab == 1
              ? historyView()
              : profile(),
        ),
      ),
    ),
    floatingActionButton: tab == 2
        ? null
        : FloatingActionButton.extended(
            onPressed: createReport,
            backgroundColor: lime,
            foregroundColor: green,
            elevation: 1,
            icon: const Icon(Icons.add_a_photo_outlined),
            label: const Text('Buat laporan'),
          ),
    bottomNavigationBar: NavigationBar(
      selectedIndex: tab,
      onDestinationSelected: (v) => setState(() => tab = v),
      backgroundColor: paper,
      indicatorColor: const Color(0xffe3eacb),
      destinations: const [
        NavigationDestination(
          icon: Icon(Icons.grid_view_outlined),
          selectedIcon: Icon(Icons.grid_view_rounded),
          label: 'Ringkasan',
        ),
        NavigationDestination(
          icon: Icon(Icons.receipt_long_outlined),
          label: 'Riwayat',
        ),
        NavigationDestination(icon: Icon(Icons.person_outline), label: 'Akun'),
      ],
    ),
  );
  Widget dashboard() => RefreshIndicator(
    onRefresh: refresh,
    child: ListView(
      padding: const EdgeInsets.fromLTRB(22, 22, 22, 100),
      children: [
        Text(
          'Halo, ${widget.session.user!.name.split(' ').first}.',
          style: const TextStyle(
            fontSize: 26,
            fontWeight: FontWeight.w600,
            letterSpacing: -.7,
          ),
        ),
        const SizedBox(height: 7),
        const Text(
          'Berikut perkembangan laporan Anda.',
          style: TextStyle(color: muted),
        ),
        const SizedBox(height: 26),
        if (loading) const LinearProgressIndicator(minHeight: 2),
        if (error != null) ErrorNotice(error!, retry: refresh),
        if (!loading && error == null) ...[
          ReportProgress(
            completed: (stats['completed'] as num? ?? 0).toInt(),
            total: (stats['total'] as num? ?? 0).toInt(),
            onTap: () => showHistory(status: 'completed'),
          ),
          const SizedBox(height: 14),
          LayoutBuilder(
            builder: (context, constraints) => Wrap(
              spacing: 12,
              runSpacing: 12,
              children: [
                stat(
                  'Menunggu',
                  'pending',
                  Icons.schedule,
                  const Color(0xff81651e),
                  constraints.maxWidth,
                ),
                stat(
                  'Diproses',
                  'in_progress',
                  Icons.local_shipping_outlined,
                  const Color(0xff527647),
                  constraints.maxWidth,
                ),
              ],
            ),
          ),
          const SizedBox(height: 24),
          Row(
            children: [
              const Expanded(
                child: Text(
                  'Laporan terbaru',
                  style: TextStyle(fontSize: 18, fontWeight: FontWeight.w600),
                ),
              ),
              TextButton(
                onPressed: showHistory,
                child: const Text('Lihat semua'),
              ),
            ],
          ),
          if (recent.isEmpty) const EmptyReports() else ...recent.map(tile),
        ],
      ],
    ),
  );
  Widget stat(
    String title,
    String key,
    IconData icon,
    Color color,
    double width,
  ) => SizedBox(
    width: (width - 12) / 2,
    child: Card(
      margin: EdgeInsets.zero,
      color: key == 'pending' ? cream : const Color(0xffe7ebdb),
      child: Padding(
        padding: const EdgeInsets.all(17),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Expanded(
                  child: Text(
                    title,
                    style: const TextStyle(fontSize: 12, color: muted),
                  ),
                ),
                Icon(icon, color: color, size: 19),
              ],
            ),
            const SizedBox(height: 17),
            Text(
              (stats[key] ?? 0).toString(),
              style: const TextStyle(
                fontSize: 30,
                fontWeight: FontWeight.w600,
                letterSpacing: -.7,
              ),
            ),
          ],
        ),
      ),
    ),
  );
  Widget historyView() => RefreshIndicator(
    onRefresh: loadHistory,
    child: ListView(
      padding: const EdgeInsets.fromLTRB(22, 20, 22, 100),
      children: [
        const Text(
          'Riwayat laporan',
          style: TextStyle(
            fontSize: 26,
            fontWeight: FontWeight.w600,
            letterSpacing: -.7,
          ),
        ),
        const SizedBox(height: 8),
        const Text(
          'Pantau setiap langkah penanganannya.',
          style: TextStyle(color: muted),
        ),
        const SizedBox(height: 22),
        TextField(
          controller: search,
          textInputAction: TextInputAction.search,
          onSubmitted: (value) {
            query = value;
            page = 1;
            loadHistory();
          },
          decoration: InputDecoration(
            hintText: 'Cari judul atau lokasi',
            prefixIcon: const Icon(Icons.search),
            suffixIcon: IconButton(
              tooltip: 'Cari',
              onPressed: () {
                query = search.text;
                page = 1;
                loadHistory();
              },
              icon: const Icon(Icons.arrow_forward),
            ),
          ),
        ),
        const SizedBox(height: 14),
        SingleChildScrollView(
          scrollDirection: Axis.horizontal,
          child: Row(
            children: [
              for (final entry in {'': 'Semua', ...statusLabels}.entries)
                Padding(
                  padding: const EdgeInsets.only(right: 8),
                  child: ChoiceChip(
                    label: Text(entry.value),
                    selected: filter == entry.key,
                    selectedColor: green,
                    backgroundColor: const Color(0xffeeeedf),
                    showCheckmark: false,
                    labelStyle: TextStyle(
                      color: filter == entry.key ? paper : green,
                      fontSize: 12,
                    ),
                    side: const BorderSide(color: Color(0xffdfe3d3)),
                    shape: const StadiumBorder(),
                    onSelected: (_) {
                      filter = entry.key;
                      page = 1;
                      loadHistory();
                    },
                  ),
                ),
            ],
          ),
        ),
        const SizedBox(height: 18),
        if (historyLoading)
          const Padding(
            padding: EdgeInsets.all(30),
            child: Center(child: CircularProgressIndicator()),
          )
        else if (historyError != null)
          ErrorNotice(historyError!, retry: loadHistory)
        else if (history != null) ...[
          if (history!.reports.isEmpty)
            const EmptyReports()
          else
            ...history!.reports.map(tile),
          Wrap(
            alignment: WrapAlignment.spaceBetween,
            crossAxisAlignment: WrapCrossAlignment.center,
            spacing: 12,
            runSpacing: 6,
            children: [
              TextButton(
                onPressed: page > 1
                    ? () {
                        page--;
                        loadHistory();
                      }
                    : null,
                child: const Text('Sebelumnya'),
              ),
              Text(
                '$page / ${history!.lastPage}',
                style: const TextStyle(color: muted, fontSize: 12),
              ),
              TextButton(
                onPressed: page < history!.lastPage
                    ? () {
                        page++;
                        loadHistory();
                      }
                    : null,
                child: const Text('Berikutnya'),
              ),
            ],
          ),
        ],
      ],
    ),
  );
  Widget profile() => ListView(
    padding: const EdgeInsets.all(24),
    children: [
      const SizedBox(height: 20),
      const Text(
        'Akun Anda',
        style: TextStyle(fontSize: 27, fontWeight: FontWeight.w600),
      ),
      const SizedBox(height: 24),
      Card(
        child: Padding(
          padding: const EdgeInsets.all(22),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                widget.session.user!.name,
                style: const TextStyle(
                  fontSize: 20,
                  fontWeight: FontWeight.w600,
                ),
              ),
              const SizedBox(height: 7),
              Text(
                widget.session.user!.email,
                style: const TextStyle(color: muted),
              ),
              const SizedBox(height: 12),
              Text(
                widget.session.user!.role == 'admin'
                    ? 'Administrator'
                    : 'Warga',
                style: const TextStyle(color: green, fontSize: 12),
              ),
            ],
          ),
        ),
      ),
      const SizedBox(height: 24),
      const Text(
        'Tentang Bersih',
        style: TextStyle(fontWeight: FontWeight.w600, fontSize: 16),
      ),
      const SizedBox(height: 10),
      const Text(
        'Laporkan sampah dengan foto dan lokasi. Tim akan meninjau dan memperbarui status penanganan. Hasil deteksi foto adalah saran kategori yang dapat Anda periksa kembali.',
        style: TextStyle(color: muted, height: 1.8),
      ),
      const SizedBox(height: 30),
      OutlinedButton.icon(
        onPressed: loggingOut ? null : logout,
        icon: const Icon(Icons.logout),
        label: Text(loggingOut ? 'Keluar...' : 'Keluar dari akun'),
      ),
      const SizedBox(height: 25),
      const Text(
        'Bersih · Versi 1.0',
        textAlign: TextAlign.center,
        style: TextStyle(fontSize: 11, color: muted),
      ),
    ],
  );
}
