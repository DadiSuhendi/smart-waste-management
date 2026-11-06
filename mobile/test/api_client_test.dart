import 'dart:convert';

import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';
import 'package:bersih/core/api_client.dart';

void main() {
  test('Bearer token and filters reach versioned reports endpoint', () async {
    final api = ApiClient(
      baseUrl: 'https://example.test/api/v1',
      client: MockClient((request) async {
        expect(request.headers['Authorization'], 'Bearer test-token');
        expect(request.url.path, '/api/v1/reports');
        expect(request.url.queryParameters, {
          'page': '2',
          'status': 'pending',
          'q': 'taman',
        });
        return http.Response(
          jsonEncode({
            'data': [],
            'meta': {'current_page': 2, 'last_page': 2, 'total': 12},
          }),
          200,
        );
      }),
    )..token = 'test-token';
    final page = await api.reports(page: 2, status: 'pending', query: 'taman');
    expect(page.page, 2);
    expect(page.total, 12);
    expect(page.reports, isEmpty);
  });
  test(
    'Validation errors are shown and expired token clears session',
    () async {
      var expired = false;
      final api = ApiClient(
        client: MockClient(
          (_) async => http.Response('{"message":"Unauthenticated."}', 401),
        ),
      )..token = 'expired';
      api.onUnauthorized = () async {
        expired = true;
      };
      await expectLater(
        api.request('GET', '/me'),
        throwsA(isA<ApiException>().having((e) => e.status, 'status', 401)),
      );
      expect(expired, isTrue);
      final invalid = ApiClient(
        client: MockClient(
          (_) async =>
              http.Response('{"errors":{"photo":["Foto wajib diisi."]}}', 422),
        ),
      );
      await expectLater(
        invalid.request('POST', '/reports'),
        throwsA(
          isA<ApiException>().having(
            (e) => e.message,
            'message',
            'Foto wajib diisi.',
          ),
        ),
      );
    },
  );
  test('Non-JSON server errors produce readable errors', () async {
    final api = ApiClient(
      client: MockClient(
        (_) async => http.Response('<html>Server error</html>', 500),
      ),
    );
    await expectLater(
      api.request('GET', '/dashboard'),
      throwsA(isA<ApiException>()),
    );
  });
}
