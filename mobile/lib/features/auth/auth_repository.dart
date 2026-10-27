import '../../core/api_client.dart';
import '../../core/api_exception.dart';
import '../../core/config.dart';
import '../../core/token_storage.dart';
import 'user.dart';

/// Semua pemanggilan API untuk autentikasi ada di sini.
class AuthRepository {
  AuthRepository(this._api, this._tokens);

  final ApiClient _api;
  final TokenStorage _tokens;

  Future<User> login({required String email, required String password}) async {
    final data = await _api.post(
      '/auth/login',
      body: {
        'email': email,
        'password': password,
        'device_name': AppConfig.deviceName,
      },
    );
    return _storeSession(data);
  }

  Future<({User user, bool verificationSent})> register({
    required String name,
    required String email,
    required String password,
    required String passwordConfirmation,
  }) async {
    final data = await _api.post(
      '/auth/register',
      body: {
        'name': name,
        'email': email,
        'password': password,
        'password_confirmation': passwordConfirmation,
        'device_name': AppConfig.deviceName,
      },
    );
    final user = await _storeSession(data);
    return (user: user, verificationSent: data['verification_sent'] == true);
  }

  Future<User> verifyEmail(String code) async {
    final data = await _api.post('/auth/email/verify', body: {'code': code});
    return User.fromJson(data['user'] as Map<String, dynamic>);
  }

  Future<void> resendCode() => _api.post('/auth/email/resend');

  /// null = belum login atau token sudah tidak berlaku. Error jaringan dilempar ulang.
  Future<User?> restoreSession() async {
    final token = await _tokens.read();
    if (token == null) return null;

    try {
      final data = await _api.get('/me');
      return User.fromJson(data['user'] as Map<String, dynamic>);
    } on ApiException catch (e) {
      if (e.statusCode == 401) {
        await _tokens.clear();
        return null;
      }
      rethrow;
    }
  }

  Future<void> logout() async {
    try {
      await _api.post('/auth/logout');
    } on ApiException {
      // Walau server tidak terjangkau, sesi di HP tetap dihapus.
    }
    await _tokens.clear();
  }

  Future<void> forgetSession() => _tokens.clear();

  Future<User> _storeSession(Map<String, dynamic> data) async {
    await _tokens.write(data['token'] as String);
    return User.fromJson(data['user'] as Map<String, dynamic>);
  }
}
