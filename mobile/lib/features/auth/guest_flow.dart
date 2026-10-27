import 'package:flutter/material.dart';

import 'auth_controller.dart';
import 'login_screen.dart';
import 'register_screen.dart';

/// Peralihan Masuk <-> Daftar tanpa menumpuk rute, jadi tidak ada layar tersisa saat login berhasil.
class GuestFlow extends StatefulWidget {
  const GuestFlow({super.key, required this.auth});

  final AuthController auth;

  @override
  State<GuestFlow> createState() => _GuestFlowState();
}

class _GuestFlowState extends State<GuestFlow> {
  bool _showRegister = false;

  void _setRegister(bool value) => setState(() => _showRegister = value);

  @override
  Widget build(BuildContext context) {
    return PopScope(
      canPop: !_showRegister, // tombol kembali di layar Daftar = ke layar Masuk
      onPopInvokedWithResult: (didPop, _) {
        if (!didPop) _setRegister(false);
      },
      child: _showRegister
          ? RegisterScreen(
              auth: widget.auth,
              onLogin: () => _setRegister(false),
            )
          : LoginScreen(
              auth: widget.auth,
              onRegister: () => _setRegister(true),
            ),
    );
  }
}
