import 'package:flutter/foundation.dart';

class AppConfig {
  /// 10.0.2.2 = alamat komputer Anda dari dalam emulator Android.
  /// Untuk HP fisik: flutter run --dart-define=API_BASE_URL=http://IP-KOMPUTER:8080/api/v1
  static const apiBaseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'http://10.0.2.2:8080/api/v1',
  );

  static final requestTimeout = Duration(seconds: kDebugMode ? 60 : 15);
  static const resendCooldownSeconds = 60;
  static const deviceName = 'mobile';
}
