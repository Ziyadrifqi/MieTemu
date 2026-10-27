import 'package:flutter/material.dart';

class AppColors {
  static const red = Color(0xFFC0392B);
  static const redLight = Color(0xFFE74C3C);
  static const redDeep = Color(0xFF8E1E14);
  static const ink = Color(0xFF2D1B18);
  static const muted = Color(0xFF7B6B67);
  static const line = Color(0xFFE6DAD6);
  static const soft = Color(0xFFFBF6F4);
  static const surface = Color(0xFFFFFFFF);
  static const success = Color(0xFF1E8449);
  static const danger = Color(0xFF922B21);

  static const brandGradient = LinearGradient(
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
    colors: [redLight, red],
  );

  // 🔽 baru: background gradient soft
  static const bgGradient = LinearGradient(
    begin: Alignment.topCenter,
    end: Alignment.bottomCenter,
    colors: [Color(0xFFFFF4F1), Color(0xFFFDF9F7), Color(0xFFF8EDE9)],
  );

  static List<BoxShadow> softShadow = [
    BoxShadow(
      color: red.withValues(alpha: 0.28),
      blurRadius: 20,
      offset: const Offset(0, 12),
    ),
    BoxShadow(
      color: red.withValues(alpha: 0.12),
      blurRadius: 6,
      offset: const Offset(0, 3),
    ),
  ];

  static List<BoxShadow> cardShadow = [
    BoxShadow(
      color: Colors.black.withValues(alpha: 0.06),
      blurRadius: 32,
      offset: const Offset(0, 12),
    ),
  ];
}

ThemeData buildAppTheme() {
  final scheme = ColorScheme.fromSeed(
    seedColor: AppColors.red,
    primary: AppColors.red,
    surface: AppColors.surface,
  );

  return ThemeData(
    useMaterial3: true,
    colorScheme: scheme,
    scaffoldBackgroundColor: AppColors.soft,
    textTheme: const TextTheme(
      headlineMedium: TextStyle(
        fontSize: 26,
        fontWeight: FontWeight.w800,
        color: AppColors.ink,
        letterSpacing: -0.4,
      ),
      titleLarge: TextStyle(
        fontSize: 20,
        fontWeight: FontWeight.w700,
        color: AppColors.ink,
      ),
      bodyMedium: TextStyle(fontSize: 14.5, color: AppColors.ink, height: 1.5),
    ),
  );
}
