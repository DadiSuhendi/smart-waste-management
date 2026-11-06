import 'package:flutter/material.dart';

import '../core/models.dart';

const green = Color(0xff143e34);
const lime = Color(0xff93ce62);
const canvas = Color(0xfff5f3eb);
const paper = Color(0xfffffefa);
const cream = Color(0xffeee9d7);
const muted = Color(0xff68766e);

class ReportProgress extends StatelessWidget {
  final int completed;
  final int total;
  final VoidCallback onTap;
  const ReportProgress({
    required this.completed,
    required this.total,
    required this.onTap,
    super.key,
  });

  @override
  Widget build(BuildContext context) {
    final progress = total == 0 ? 0.0 : (completed / total).clamp(0.0, 1.0);
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.fromLTRB(22, 22, 22, 14),
      decoration: BoxDecoration(
        color: green,
        borderRadius: BorderRadius.circular(16),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Row(
            children: [
              Expanded(
                child: Text(
                  'Laporan selesai ditangani',
                  style: TextStyle(color: Color(0xffd1dfc7), fontSize: 13),
                ),
              ),
              Icon(Icons.eco_outlined, color: lime, size: 22),
            ],
          ),
          const SizedBox(height: 14),
          Text.rich(
            TextSpan(
              children: [
                TextSpan(
                  text: '$completed',
                  style: const TextStyle(
                    fontSize: 38,
                    fontWeight: FontWeight.w600,
                    color: paper,
                  ),
                ),
                TextSpan(
                  text: '  dari $total laporan',
                  style: const TextStyle(
                    fontSize: 13,
                    color: Color(0xffd1dfc7),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 16),
          LinearProgressIndicator(
            value: progress,
            color: lime,
            backgroundColor: const Color(0xff3b5d43),
            minHeight: 6,
            borderRadius: BorderRadius.circular(8),
            semanticsLabel: 'Persentase laporan selesai',
          ),
          const SizedBox(height: 6),
          Wrap(
            crossAxisAlignment: WrapCrossAlignment.center,
            alignment: WrapAlignment.spaceBetween,
            spacing: 16,
            children: [
              Text(
                '${(progress * 100).round()}% terselesaikan',
                style: const TextStyle(color: Color(0xffd1dfc7), fontSize: 12),
              ),
              TextButton(
                onPressed: onTap,
                style: TextButton.styleFrom(foregroundColor: paper),
                child: const Text(
                  'Lihat laporan',
                  style: TextStyle(fontSize: 12),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class Brand extends StatelessWidget {
  const Brand({super.key});
  @override
  Widget build(BuildContext context) => Row(
    mainAxisSize: MainAxisSize.min,
    children: [
      Container(
        padding: const EdgeInsets.all(8),
        decoration: BoxDecoration(
          color: green,
          borderRadius: BorderRadius.circular(12),
        ),
        child: const Icon(
          Icons.eco_outlined,
          color: Color(0xffe8edc8),
          size: 23,
        ),
      ),
      const SizedBox(width: 10),
      const Flexible(
        child: FittedBox(
          fit: BoxFit.scaleDown,
          child: Text(
            'bersih.',
            style: TextStyle(
              fontSize: 28,
              fontWeight: FontWeight.w700,
              letterSpacing: -1,
            ),
          ),
        ),
      ),
    ],
  );
}

class StatusBadge extends StatelessWidget {
  final String status;
  const StatusBadge(this.status, {super.key});
  @override
  Widget build(BuildContext context) {
    final color = switch (status) {
      'pending' => const Color(0xff81651e),
      'in_progress' => const Color(0xff426b75),
      'rejected' => const Color(0xff994f3e),
      _ => green,
    };
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 5),
      decoration: BoxDecoration(
        color: color.withValues(alpha: .09),
        borderRadius: BorderRadius.circular(20),
      ),
      child: Text(
        statusLabels[status] ?? status,
        style: TextStyle(
          color: color,
          fontSize: 11,
          fontWeight: FontWeight.w600,
        ),
      ),
    );
  }
}

class ErrorNotice extends StatelessWidget {
  final String message;
  final VoidCallback? retry;
  const ErrorNotice(this.message, {this.retry, super.key});
  @override
  Widget build(BuildContext context) => Container(
    width: double.infinity,
    margin: const EdgeInsets.symmetric(vertical: 12),
    padding: const EdgeInsets.all(16),
    decoration: BoxDecoration(
      color: const Color(0xfffff3ef),
      borderRadius: BorderRadius.circular(10),
    ),
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(message, style: const TextStyle(color: Color(0xffa05f51))),
        if (retry != null)
          TextButton(onPressed: retry, child: const Text('Coba lagi')),
      ],
    ),
  );
}

class ReportTile extends StatelessWidget {
  final WasteReport report;
  final Map<String, String> headers;
  final VoidCallback onTap;
  const ReportTile({
    required this.report,
    required this.headers,
    required this.onTap,
    super.key,
  });
  @override
  Widget build(BuildContext context) => Card(
    margin: const EdgeInsets.only(bottom: 12),
    child: InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(12),
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Row(
          children: [
            ClipRRect(
              borderRadius: BorderRadius.circular(8),
              child: Image.network(
                report.photoUrl,
                headers: headers,
                width: 66,
                height: 80,
                fit: BoxFit.cover,
                errorBuilder: (_, error, stack) => Container(
                  width: 66,
                  height: 80,
                  color: cream,
                  child: const Icon(Icons.image_outlined, color: muted),
                ),
              ),
            ),
            const SizedBox(width: 13),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    report.reference,
                    style: const TextStyle(color: muted, fontSize: 11),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    report.title,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                      fontWeight: FontWeight.w600,
                      fontSize: 14,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    report.address,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(color: muted, fontSize: 12),
                  ),
                  const SizedBox(height: 9),
                  Wrap(
                    spacing: 10,
                    runSpacing: 6,
                    crossAxisAlignment: WrapCrossAlignment.center,
                    children: [
                      StatusBadge(report.status),
                      Text(
                        displayDate(report.createdAt),
                        style: const TextStyle(color: muted, fontSize: 11),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    ),
  );
}

class EmptyReports extends StatelessWidget {
  const EmptyReports({super.key});
  @override
  Widget build(BuildContext context) => const Padding(
    padding: EdgeInsets.symmetric(vertical: 45),
    child: Column(
      children: [
        Icon(Icons.inbox_outlined, size: 48, color: muted),
        SizedBox(height: 16),
        Text(
          'Belum ada laporan',
          style: TextStyle(fontWeight: FontWeight.w600, fontSize: 18),
        ),
        SizedBox(height: 8),
        Text(
          'Buat laporan pertama atau ubah filter pencarian.',
          textAlign: TextAlign.center,
          style: TextStyle(color: muted),
        ),
      ],
    ),
  );
}
