class ApiException implements Exception {
  const ApiException(
    this.message, {
    this.statusCode,
    this.code,
    this.fieldErrors = const {},
  });

  const ApiException.offline()
    : this('Tidak dapat terhubung ke server. Periksa koneksi internet Anda.');

  final String message;
  final int? statusCode;

  /// Kode khusus dari server, misalnya "email_not_verified".
  final String? code;

  /// Pesan validasi per kolom, misalnya {'email': ['Email ini sudah terdaftar.']}.
  final Map<String, List<String>> fieldErrors;

  String? fieldError(String field) {
    final errors = fieldErrors[field];
    return (errors == null || errors.isEmpty) ? null : errors.first;
  }

  @override
  String toString() => message;
}
