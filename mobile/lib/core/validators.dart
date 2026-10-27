class Validators {
  static String? required(String? value, String label) =>
      (value == null || value.trim().isEmpty) ? '$label wajib diisi.' : null;

  static String? email(String? value) {
    final empty = required(value, 'Email');
    if (empty != null) return empty;

    return RegExp(r'^[^@\s]+@[^@\s]+\.[^@\s]+$').hasMatch(value!.trim())
        ? null
        : 'Format email tidak valid.';
  }

  /// Aturan sama dengan server: minimal 8 karakter.
  static String? newPassword(String? value) {
    if (value == null || value.isEmpty) return 'Password wajib diisi.';
    return value.length < 8 ? 'Password minimal 8 karakter.' : null;
  }

  static String? Function(String?) sameAs(String Function() other) =>
      (value) => value == other() ? null : 'Konfirmasi password tidak sama.';
}
