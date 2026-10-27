import 'package:flutter/material.dart';

import 'auth_controller.dart';
import 'forgot_password_screen.dart';
import 'login_screen.dart';
import 'register_screen.dart';

enum _GuestPage { login, register, forgotPassword }

/// Peralihan Masuk / Daftar / Lupa password tanpa menumpuk rute,
/// jadi tidak ada layar tersisa saat login berhasil.
class GuestFlow extends StatefulWidget {
  const GuestFlow({super.key, required this.auth});

  final AuthController auth;

  @override
  State<GuestFlow> createState() => _GuestFlowState();
}

class _GuestFlowState extends State<GuestFlow> {
  _GuestPage _page = _GuestPage.login;

  void _go(_GuestPage page) => setState(() => _page = page);

  @override
  Widget build(BuildContext context) {
    return PopScope(
      canPop:
          _page ==
          _GuestPage.login, // tombol kembali di layar lain = ke layar Masuk
      onPopInvokedWithResult: (didPop, _) {
        if (!didPop) _go(_GuestPage.login);
      },
      child: switch (_page) {
        _GuestPage.login => LoginScreen(
          auth: widget.auth,
          onRegister: () => _go(_GuestPage.register),
          onForgotPassword: () => _go(_GuestPage.forgotPassword),
        ),
        _GuestPage.register => RegisterScreen(
          auth: widget.auth,
          onLogin: () => _go(_GuestPage.login),
        ),
        _GuestPage.forgotPassword => ForgotPasswordScreen(
          auth: widget.auth,
          onBack: () => _go(_GuestPage.login),
        ),
      },
    );
  }
}
