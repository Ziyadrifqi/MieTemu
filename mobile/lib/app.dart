import 'package:flutter/material.dart';

import 'core/theme.dart';
import 'features/auth/auth_controller.dart';
import 'features/auth/guest_flow.dart';
import 'features/auth/verify_email_screen.dart';
import 'features/home/home_screen.dart';
import 'features/startup/startup_screens.dart';

class MieTemuApp extends StatelessWidget {
  const MieTemuApp({super.key, required this.auth});

  final AuthController auth;

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'MieTemu',
      debugShowCheckedModeBanner: false,
      theme: buildAppTheme(),
      // Layar root ditentukan sepenuhnya oleh status sesi.
      home: ListenableBuilder(
        listenable: auth,
        builder: (context, _) => switch (auth.status) {
          AuthStatus.loading => const SplashScreen(),
          AuthStatus.failure => ConnectionErrorScreen(
            message: auth.failureMessage ?? 'Coba lagi beberapa saat.',
            onRetry: auth.start,
          ),
          AuthStatus.unauthenticated => GuestFlow(auth: auth),
          AuthStatus.needsVerification => VerifyEmailScreen(auth: auth),
          AuthStatus.authenticated => HomeScreen(auth: auth),
        },
      ),
    );
  }
}
