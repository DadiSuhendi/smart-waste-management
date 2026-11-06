import 'dart:async';
import 'dart:convert';
import 'dart:io';

import 'package:http/http.dart' as http;
import 'package:image_picker/image_picker.dart';

import 'models.dart';

class ApiException implements Exception {
  final String message;
  final int status;
  const ApiException(this.message, [this.status = 0]);
  @override
  String toString() => message;
}

class ApiClient {
  final String baseUrl;
  final http.Client client;
  String? token;
  Future<void> Function()? onUnauthorized;
  ApiClient({String? baseUrl, http.Client? client})
    : baseUrl =
          (baseUrl ??
                  const String.fromEnvironment(
                    'API_BASE_URL',
                    defaultValue: 'http://10.0.2.2:8123/api/v1',
                  ))
              .replaceAll(RegExp(r'/+$'), ''),
      client = client ?? http.Client();
  Map<String, String> get headers => {
    'Accept': 'application/json',
    if (token != null) 'Authorization': 'Bearer ${token!}',
  };
  Future<Map<String, dynamic>> decode(http.Response response) async {
    Map<String, dynamic> body;
    try {
      body = jsonDecode(response.body) as Map<String, dynamic>;
    } catch (_) {
      throw ApiException(
        'Respons server tidak dapat dibaca. Coba lagi.',
        response.statusCode,
      );
    }
    if (response.statusCode >= 400) {
      if (response.statusCode == 401 && token != null) {
        await onUnauthorized?.call();
      }
      final errors = body['errors'];
      final message = errors is Map
          ? errors.values.expand((value) => value as List).join('\n')
          : (body['message'] ?? 'Permintaan gagal. Coba lagi.').toString();
      throw ApiException(message, response.statusCode);
    }
    return body;
  }

  Future<Map<String, dynamic>> request(
    String method,
    String path, {
    Map<String, dynamic>? body,
    Map<String, String>? query,
  }) async {
    try {
      final uri = Uri.parse(baseUrl + path).replace(queryParameters: query);
      final request = http.Request(method, uri)
        ..headers.addAll({...headers, 'Content-Type': 'application/json'});
      if (body != null) {
        request.body = jsonEncode(body);
      }
      final response = await client
          .send(request)
          .then(http.Response.fromStream)
          .timeout(const Duration(seconds: 30));
      return await decode(response);
    } on TimeoutException {
      throw const ApiException(
        'Koneksi terlalu lama. Periksa jaringan lalu coba lagi.',
      );
    } on SocketException {
      throw const ApiException(
        'Tidak dapat terhubung ke server. Periksa koneksi Anda.',
      );
    } on http.ClientException {
      throw const ApiException(
        'Tidak dapat terhubung ke server. Periksa koneksi Anda.',
      );
    }
  }

  Future<Map<String, dynamic>> upload(
    String path,
    XFile photo,
    Map<String, String> fields,
  ) async {
    if (await photo.length() > 5 * 1024 * 1024) {
      throw const ApiException('Ukuran foto maksimal 5 MB.');
    }
    try {
      final request = http.MultipartRequest('POST', Uri.parse(baseUrl + path))
        ..headers.addAll(headers)
        ..fields.addAll(fields)
        ..files.add(
          await http.MultipartFile.fromPath(
            'photo',
            photo.path,
            filename: photo.name,
          ),
        );
      final response = await client
          .send(request)
          .then(http.Response.fromStream)
          .timeout(const Duration(seconds: 45));
      return await decode(response);
    } on TimeoutException {
      throw const ApiException(
        'Pengiriman terlalu lama. Periksa riwayat sebelum mengirim ulang.',
      );
    } on SocketException {
      throw const ApiException(
        'Koneksi terputus. Periksa riwayat sebelum mengirim ulang.',
      );
    } on http.ClientException {
      throw const ApiException(
        'Koneksi terputus. Coba lagi setelah memeriksa riwayat.',
      );
    }
  }

  Future<ReportPage> reports({
    int page = 1,
    String status = '',
    String query = '',
  }) async => ReportPage.fromJson(
    await request(
      'GET',
      '/reports',
      query: {
        'page': page.toString(),
        if (status.isNotEmpty) 'status': status,
        if (query.isNotEmpty) 'q': query,
      },
    ),
  );
  Future<WasteReport> report(int id) async =>
      WasteReport.fromJson((await request('GET', '/reports/$id'))['data']);
}
