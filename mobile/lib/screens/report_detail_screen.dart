import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../core/api_client.dart';
import '../core/models.dart';
import '../widgets/common.dart';

class ReportDetailScreen extends StatefulWidget {
  final ApiClient api;
  final int id;
  const ReportDetailScreen({required this.api, required this.id, super.key});
  @override
  State<ReportDetailScreen> createState() => _ReportDetailScreenState();
}

class _ReportDetailScreenState extends State<ReportDetailScreen> {
  WasteReport? report;
  String? error;
  bool loading = true;
  @override
  void initState() {
    super.initState();
    load();
  }

  Future<void> load() async {
    setState(() {
      loading = true;
      error = null;
    });
    try {
      final result = await widget.api.report(widget.id);
      if (mounted) setState(() => report = result);
    } catch (e) {
      if (mounted) setState(() => error = e.toString());
    } finally {
      if (mounted) setState(() => loading = false);
    }
  }

  @override
  Widget build(BuildContext context) => Scaffold(
    appBar: AppBar(
      title: const Text(
        'Detail laporan',
        style: TextStyle(fontSize: 19, fontWeight: FontWeight.w600),
      ),
      actions: [
        IconButton(
          tooltip: 'Perbarui status',
          onPressed: loading ? null : load,
          icon: const Icon(Icons.refresh),
        ),
      ],
    ),
    body: SafeArea(
      child: Center(
        child: ConstrainedBox(
          constraints: const BoxConstraints(maxWidth: 680),
          child: loading
              ? const Center(child: CircularProgressIndicator())
              : error != null
              ? Padding(
                  padding: const EdgeInsets.all(22),
                  child: ErrorNotice(error!, retry: load),
                )
              : details(report!),
        ),
      ),
    ),
  );
  Widget details(WasteReport report) => RefreshIndicator(
    onRefresh: load,
    child: ListView(
      padding: const EdgeInsets.all(22),
      children: [
        Row(
          children: [
            Text(
              report.reference,
              style: const TextStyle(color: muted, fontSize: 12),
            ),
            const Spacer(),
            StatusBadge(report.status),
          ],
        ),
        const SizedBox(height: 16),
        Text(
          report.title,
          style: const TextStyle(
            fontSize: 26,
            fontWeight: FontWeight.w600,
            letterSpacing: -.7,
          ),
        ),
        const SizedBox(height: 9),
        Text(
          '${displayDate(report.createdAt)} · ${categoryLabels[report.category] ?? report.category}',
          style: const TextStyle(color: muted, fontSize: 12),
        ),
        const SizedBox(height: 24),
        ClipRRect(
          borderRadius: BorderRadius.circular(12),
          child: Image.network(
            report.photoUrl,
            headers: widget.api.headers,
            height: 260,
            fit: BoxFit.cover,
            errorBuilder: (_, error, stack) => const SizedBox(
              height: 160,
              child: Center(child: Text('Foto tidak dapat dimuat')),
            ),
          ),
        ),
        const SizedBox(height: 22),
        const Text(
          'Kondisi di lokasi',
          style: TextStyle(fontSize: 17, fontWeight: FontWeight.w600),
        ),
        const SizedBox(height: 10),
        Text(
          report.description,
          style: const TextStyle(color: muted, height: 1.8),
        ),
        if (report.aiCategory != null) ...[
          const SizedBox(height: 12),
          Text(
            'Saran AI: ${categoryLabels[report.aiCategory] ?? report.aiCategory!} · ${((report.aiConfidence ?? 0) * 100).toStringAsFixed(1)}%',
            style: const TextStyle(color: green, fontSize: 12),
          ),
        ],
        const SizedBox(height: 24),
        Card(
          margin: EdgeInsets.zero,
          child: Padding(
            padding: const EdgeInsets.all(18),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Icon(Icons.location_on_outlined, color: green),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        report.address,
                        style: const TextStyle(fontWeight: FontWeight.w600),
                      ),
                      const SizedBox(height: 7),
                      Text(
                        '${report.latitude}, ${report.longitude}',
                        style: const TextStyle(color: muted, fontSize: 12),
                      ),
                      TextButton(
                        onPressed: () async {
                          await Clipboard.setData(
                            ClipboardData(
                              text:
                                  'https://www.openstreetmap.org/?mlat=${report.latitude}&mlon=${report.longitude}#map=17/${report.latitude}/${report.longitude}',
                            ),
                          );
                          if (mounted) {
                            ScaffoldMessenger.of(context).showSnackBar(
                              const SnackBar(
                                content: Text('Tautan lokasi disalin.'),
                              ),
                            );
                          }
                        },
                        child: const Text('Salin tautan peta'),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ),
        const SizedBox(height: 28),
        const Text(
          'Riwayat penanganan',
          style: TextStyle(fontSize: 18, fontWeight: FontWeight.w600),
        ),
        const SizedBox(height: 18),
        ...report.histories.map(
          (h) => Padding(
            padding: const EdgeInsets.only(bottom: 22),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Container(
                  margin: const EdgeInsets.only(top: 5),
                  width: 9,
                  height: 9,
                  decoration: const BoxDecoration(
                    color: green,
                    shape: BoxShape.circle,
                  ),
                ),
                const SizedBox(width: 15),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        statusLabels[h['status']] ?? h['status'],
                        style: const TextStyle(fontWeight: FontWeight.w600),
                      ),
                      const SizedBox(height: 5),
                      Text(
                        '${displayDate(DateTime.parse(h['created_at']).toLocal())} · ${h['actor']}',
                        style: const TextStyle(fontSize: 11, color: muted),
                      ),
                      if (h['note'] != null) ...[
                        const SizedBox(height: 8),
                        Text(
                          h['note'],
                          style: const TextStyle(
                            color: muted,
                            height: 1.6,
                            fontSize: 13,
                          ),
                        ),
                      ],
                    ],
                  ),
                ),
              ],
            ),
          ),
        ),
        if (report.histories.isEmpty)
          const Text(
            'Belum ada perubahan status.',
            style: TextStyle(color: muted),
          ),
      ],
    ),
  );
}
