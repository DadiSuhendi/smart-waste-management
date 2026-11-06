import 'package:flutter/foundation.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

import 'api_client.dart';
import 'models.dart';

class Session extends ChangeNotifier {
  final ApiClient api;
  final FlutterSecureStorage storage;
  UserProfile? user;
  bool loading = true;
  String? error;
  Session({ApiClient? api, FlutterSecureStorage? storage})
    : api = api ?? ApiClient(),
      storage = storage ?? const FlutterSecureStorage() {
    this.api.onUnauthorized = clear;
  }
  Future<void> restore() async {
    loading = true;
    error = null;
    notifyListeners();
    try {
      api.token = await storage.read(key: 'bersih_token');
      if (api.token != null) {
        user = UserProfile.fromJson((await api.request('GET', '/me'))['data']);
      }
    } on ApiException catch (e) {
      if (e.status != 401) {
        error = e.message;
      }
    } catch (_) {
      error = 'Penyimpanan sesi belum tersedia. Silakan coba lagi.';
    }
    loading = false;
    notifyListeners();
  }

  Future<void> authenticate(
    Map<String, dynamic> fields, {
    bool register = false,
  }) async {
    final result = await api.request(
      'POST',
      register ? '/register' : '/login',
      body: {...fields, 'device_name': 'Bersih mobile'},
    );
    final token = result['token'] as String;
    await storage.write(key: 'bersih_token', value: token);
    api.token = token;
    user = UserProfile.fromJson(result['user']);
    error = null;
    notifyListeners();
  }

  Future<void> clear() async {
    await storage.delete(key: 'bersih_token');
    api.token = null;
    user = null;
    error = null;
    notifyListeners();
  }

  Future<void> logout() async {
    await api.request('POST', '/logout');
    await clear();
  }
}
