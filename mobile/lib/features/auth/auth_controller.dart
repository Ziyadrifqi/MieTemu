import 'package:flutter/foundation.dart';

import '../../core/api_exception.dart';
import 'auth_repository.dart';
import 'user.dart';

enum AuthStatus {
  loading,
  failure,
  unauthenticated,
  needsVerification,
  authenticated,
}

/// Satu sumber kebenaran untuk status sesi; layar hanya membaca dan memanggil method di sini.
class AuthController extends ChangeNotifier {
  AuthController(this._repository);

  final AuthRepository _repository;

  AuthStatus _status = AuthStatus.loading;
  User? _user;
  String? _failureMessage;
  DateTime? _codeSentAt;

  AuthStatus get status => _status;
  User? get user => _user;
  String? get failureMessage => _failureMessage;

  /// Kapan kode verifikasi terakhir dikirim (untuk hitung mundur kirim ulang).
  DateTime? get codeSentAt => _codeSentAt;

  Future<void> start() async {
    _status = AuthStatus.loading;
    _failureMessage = null;
    notifyListeners();

    try {
      final user = await _repository.restoreSession();
      if (user == null) {
        _status = AuthStatus.unauthenticated;
        notifyListeners();
      } else {
        _setUser(user);
      }
    } on ApiException catch (e) {
      _failureMessage = e.message;
      _status = AuthStatus.failure;
      notifyListeners();
    }
  }

  Future<void> login({required String email, required String password}) async {
    _setUser(await _repository.login(email: email, password: password));
  }

  Future<void> register({
    required String name,
    required String email,
    required String password,
    required String passwordConfirmation,
  }) async {
    final result = await _repository.register(
      name: name,
      email: email,
      password: password,
      passwordConfirmation: passwordConfirmation,
    );
    if (result.verificationSent) _codeSentAt = DateTime.now();
    _setUser(result.user);
  }

  Future<void> verifyEmail(String code) async {
    _setUser(await _repository.verifyEmail(code));
  }

  Future<void> resendCode() async {
    await _repository.resendCode();
    _codeSentAt = DateTime.now();
    notifyListeners();
  }

  Future<void> logout() async {
    await _repository.logout();
    _reset();
  }

  /// Dipanggil ApiClient saat token ditolak server (401).
  Future<void> handleUnauthorized() async {
    await _repository.forgetSession();
    _reset();
  }

  void _reset() {
    _user = null;
    _codeSentAt = null;
    _status = AuthStatus.unauthenticated;
    notifyListeners();
  }

  void _setUser(User user) {
    _user = user;
    _status = user.emailVerified
        ? AuthStatus.authenticated
        : AuthStatus.needsVerification;
    notifyListeners();
  }
}
