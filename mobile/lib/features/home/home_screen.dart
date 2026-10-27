import 'package:flutter/material.dart';

import '../../core/dialogs.dart';
import '../../core/theme.dart';
import '../auth/auth_controller.dart';

/// Placeholder; daftar cabang dan tiket antrean hadir di Step 14.
class HomeScreen extends StatelessWidget {
  const HomeScreen({super.key, required this.auth});

  final AuthController auth;

  Future<void> _confirmLogout(BuildContext context) async {
    final ok = await confirmDialog(
      context,
      title: 'Keluar',
      message: 'Yakin ingin keluar dari akun ini?',
      confirmLabel: 'Ya, keluar',
      destructive: true,
    );
    if (ok) await auth.logout();
  }

  @override
  Widget build(BuildContext context) {
    final user = auth.user;

    return Scaffold(
      appBar: AppBar(
        title: const Text('MieTemu'),
        backgroundColor: AppColors.red,
        foregroundColor: Colors.white,
        actions: [
          IconButton(
            tooltip: 'Keluar',
            icon: const Icon(Icons.logout),
            onPressed: () => _confirmLogout(context),
          ),
        ],
      ),
      body: SafeArea(
        child: Center(
          child: ConstrainedBox(
            constraints: const BoxConstraints(maxWidth: 480),
            child: Padding(
              padding: const EdgeInsets.all(24),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  const Icon(
                    Icons.ramen_dining,
                    size: 64,
                    color: AppColors.red,
                  ),
                  const SizedBox(height: 16),
                  Text(
                    'Halo, ${user?.name ?? ''}!',
                    textAlign: TextAlign.center,
                    style: Theme.of(context).textTheme.headlineSmall,
                  ),
                  const SizedBox(height: 6),
                  Text(
                    user?.email ?? '',
                    style: const TextStyle(color: AppColors.muted),
                  ),
                  const SizedBox(height: 24),
                  Container(
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(16),
                      border: Border.all(color: AppColors.line),
                    ),
                    child: const Text(
                      'Akun Anda sudah aktif. Daftar cabang dan ambil nomor antrean hadir di langkah berikutnya.',
                      textAlign: TextAlign.center,
                      style: TextStyle(height: 1.4),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}
