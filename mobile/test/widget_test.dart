import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:bersih/core/session.dart';
import 'package:bersih/main.dart';
import 'package:bersih/core/api_client.dart';
import 'package:bersih/core/models.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';

void main() {
  testWidgets(
    'Compact dashboard opens completed reports and resets all reports',
    (tester) async {
      tester.view.physicalSize = const Size(320, 800);
      tester.view.devicePixelRatio = 1;
      tester.platformDispatcher.textScaleFactorTestValue = 1.3;
      addTearDown(tester.view.resetPhysicalSize);
      addTearDown(tester.view.resetDevicePixelRatio);
      addTearDown(tester.platformDispatcher.clearTextScaleFactorTestValue);
      final reportRequests = <Uri>[];
      final client = MockClient((request) async {
        if (request.url.path.endsWith('/dashboard')) {
          return http.Response(
            jsonEncode({
              'data': {
                'stats': {
                  'total': 12,
                  'completed': 6,
                  'pending': 4,
                  'in_progress': 2,
                },
              },
            }),
            200,
          );
        }
        reportRequests.add(request.url);
        return http.Response(
          jsonEncode({
            'data': [],
            'meta': {'current_page': 1, 'last_page': 1, 'total': 0},
          }),
          200,
        );
      });
      final session = Session(api: ApiClient(client: client))
        ..loading = false
        ..user = const UserProfile(
          id: 1,
          name: 'Nadia Putri',
          email: 'nadia@example.test',
          role: 'user',
        );
      addTearDown(session.dispose);
      addTearDown(client.close);
      await tester.pumpWidget(BersihApp(session: session));
      await tester.pumpAndSettle();
      expect(tester.takeException(), isNull);
      await tester.tap(find.text('Lihat laporan'));
      await tester.pumpAndSettle();
      expect(reportRequests.last.queryParameters['status'], 'completed');
      await tester.tap(find.text('Ringkasan'));
      await tester.pumpAndSettle();
      await tester.scrollUntilVisible(find.text('Lihat semua'), 180);
      await tester.pumpAndSettle();
      await tester.tap(find.text('Lihat semua'));
      await tester.pumpAndSettle();
      expect(
        reportRequests.last.queryParameters.containsKey('status'),
        isFalse,
      );
      expect(tester.takeException(), isNull);
    },
  );

  testWidgets('Login validates input and registration is reachable', (
    tester,
  ) async {
    FlutterSecureStorage.setMockInitialValues({});
    final session = Session();
    await session.restore();
    await tester.pumpWidget(BersihApp(session: session));
    await tester.pumpAndSettle();
    expect(find.text('Selamat datang kembali.'), findsOneWidget);
    await tester.tap(find.widgetWithText(FilledButton, 'Masuk'));
    await tester.pumpAndSettle();
    expect(find.text('Masukkan email yang valid'), findsOneWidget);
    expect(find.text('Kata sandi wajib diisi'), findsOneWidget);
    await tester.ensureVisible(find.text('Belum punya akun? Daftar'));
    await tester.tap(find.text('Belum punya akun? Daftar'));
    await tester.pumpAndSettle();
    expect(find.text('Nama lengkap'), findsOneWidget);
    expect(find.text('Konfirmasi kata sandi'), findsOneWidget);
    session.dispose();
  });
}
