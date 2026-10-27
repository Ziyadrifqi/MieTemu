import 'dart:ui';

import 'package:flutter/material.dart';

import '../core/theme.dart';

class AuthScaffold extends StatelessWidget {
  const AuthScaffold({
    super.key,
    required this.title,
    required this.subtitle,
    required this.child,
  });

  final String title;
  final String subtitle;
  final Widget child;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Stack(
        children: [
          // ===== BACKGROUND GRADIENT =====
          Container(
            decoration: const BoxDecoration(gradient: AppColors.bgGradient),
          ),

          // ===== DEKORASI LINGKARAN BLUR =====
          Positioned(
            top: -60,
            right: -60,
            child: _blurCircle(180, AppColors.redLight.withOpacity(0.18)),
          ),
          Positioned(
            top: 200,
            left: -80,
            child: _blurCircle(200, AppColors.red.withOpacity(0.10)),
          ),
          Positioned(
            bottom: -80,
            right: -60,
            child: _blurCircle(220, AppColors.redDeep.withOpacity(0.08)),
          ),

          // ===== KONTEN =====
          SafeArea(
            child: SingleChildScrollView(
              padding: const EdgeInsets.fromLTRB(24, 24, 24, 24),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  const SizedBox(height: 12),

                  // ===== LOGO MIE =====
                  Center(
                    child: Container(
                      width: 88,
                      height: 88,
                      decoration: BoxDecoration(
                        gradient: AppColors.brandGradient,
                        shape: BoxShape.circle,
                        boxShadow: AppColors.softShadow,
                      ),
                      alignment: Alignment.center,
                      child: const Icon(
                        Icons.ramen_dining_rounded,
                        color: Colors.white,
                        size: 44,
                      ),
                    ),
                  ),
                  const SizedBox(height: 24),

                  // ===== TITLE =====
                  Text(
                    title,
                    textAlign: TextAlign.center,
                    style: const TextStyle(
                      fontSize: 28,
                      fontWeight: FontWeight.w800,
                      color: AppColors.ink,
                      letterSpacing: -0.5,
                    ),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    subtitle,
                    textAlign: TextAlign.center,
                    style: const TextStyle(
                      fontSize: 14,
                      height: 1.5,
                      color: AppColors.muted,
                    ),
                  ),
                  const SizedBox(height: 32),

                  // ===== FORM CARD (GLASSMORPHISM HALUS) =====
                  ClipRRect(
                    borderRadius: BorderRadius.circular(28),
                    child: BackdropFilter(
                      filter: ImageFilter.blur(sigmaX: 14, sigmaY: 14),
                      child: Container(
                        padding: const EdgeInsets.all(22),
                        decoration: BoxDecoration(
                          color: Colors.white.withOpacity(0.85),
                          borderRadius: BorderRadius.circular(28),
                          border: Border.all(
                            color: Colors.white.withOpacity(0.9),
                            width: 1.2,
                          ),
                          boxShadow: AppColors.cardShadow,
                        ),
                        child: child,
                      ),
                    ),
                  ),

                  const SizedBox(height: 24),

                  // ===== FOOTER =====
                  Text(
                    '© 2026 Antrean Mie',
                    textAlign: TextAlign.center,
                    style: TextStyle(
                      fontSize: 12,
                      color: AppColors.muted.withOpacity(0.7),
                      letterSpacing: 0.3,
                    ),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _blurCircle(double size, Color color) {
    return Container(
      width: size,
      height: size,
      decoration: BoxDecoration(shape: BoxShape.circle, color: color),
    );
  }
}
