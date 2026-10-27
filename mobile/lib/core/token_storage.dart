import 'package:flutter_secure_storage/flutter_secure_storage.dart';

/// Token login disimpan terenkripsi (Android Keystore), bukan di preferensi biasa.
class TokenStorage {
  TokenStorage([FlutterSecureStorage? storage])
    : _storage = storage ?? const FlutterSecureStorage();

  static const _key = 'auth_token';
  final FlutterSecureStorage _storage;

  Future<String?> read() async {
    try {
      return await _storage.read(key: _key);
    } catch (_) {
      // Keystore rusak (mis. setelah restore backup): anggap belum login.
      await clear();
      return null;
    }
  }

  Future<void> write(String token) => _storage.write(key: _key, value: token);

  Future<void> clear() async {
    try {
      await _storage.delete(key: _key);
    } catch (_) {
      // Tidak ada yang bisa dilakukan; token memang tidak terbaca.
    }
  }
}
