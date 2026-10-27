const _wibOffset = Duration(hours: 7);

String _two(int n) => n.toString().padLeft(2, '0');

/// Jam dalam WIB (UTC+7) berformat HH:mm:ss, tidak bergantung pada zona waktu HP.
String formatWibClock(DateTime time) {
  final wib = time.toUtc().add(_wibOffset);
  return '${_two(wib.hour)}:${_two(wib.minute)}:${_two(wib.second)}';
}
