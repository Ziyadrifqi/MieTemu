import 'package:flutter/material.dart';

import '../../core/theme.dart';
import '../../widgets/auth_scaffold.dart';
import '../../widgets/primary_button.dart';

class SplashScreen extends StatelessWidget {
  const SplashScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return const Scaffold(
      body: DecoratedBox(
        decoration: BoxDecoration(gradient: AppColors.brandGradient),
        child: Center(child: CircularProgressIndicator(color: Colors.white)),
      ),
    );
  }
}

/// Tampil saat sesi tersimpan tidak bisa dicek karena server tidak terjangkau.
class ConnectionErrorScreen extends StatelessWidget {
  const ConnectionErrorScreen({
    super.key,
    required this.message,
    required this.onRetry,
  });

  final String message;
  final Future<void> Function() onRetry;

  @override
  Widget build(BuildContext context) {
    return AuthScaffold(
      title: 'Tidak dapat terhubung',
      subtitle: message,
      child: PrimaryButton(label: 'Coba lagi', onPressed: () => onRetry()),
    );
  }
}
