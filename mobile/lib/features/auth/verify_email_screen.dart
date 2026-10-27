import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../../core/api_exception.dart';
import '../../core/config.dart';
import '../../core/dialogs.dart';
import '../../core/messages.dart';
import '../../core/theme.dart';
import '../../widgets/app_text_field.dart';
import '../../widgets/auth_scaffold.dart';
import '../../widgets/error_banner.dart';
import '../../widgets/primary_button.dart';
import 'auth_controller.dart';

class VerifyEmailScreen extends StatefulWidget {
  const VerifyEmailScreen({super.key, required this.auth});

  final AuthController auth;

  @override
  State<VerifyEmailScreen> createState() => _VerifyEmailScreenState();
}

class _VerifyEmailScreenState extends State<VerifyEmailScreen> {
  final _code = TextEditingController();
  Timer? _timer;
  int _cooldown = 0;
  bool _verifying = false;
  bool _resending = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _syncCooldown();
    _timer = Timer.periodic(const Duration(seconds: 1), (_) => _syncCooldown());
  }

  @override
  void dispose() {
    _timer?.cancel();
    _code.dispose();
    super.dispose();
  }

  void _syncCooldown() {
    final sentAt = widget.auth.codeSentAt;
    final remaining = sentAt == null
        ? 0
        : AppConfig.resendCooldownSeconds -
              DateTime.now().difference(sentAt).inSeconds;
    final next = remaining < 0 ? 0 : remaining;

    if (next != _cooldown && mounted) setState(() => _cooldown = next);
  }

  Future<void> _verify() async {
    final code = _code.text.trim();
    if (code.length != 6) {
      setState(() => _error = 'Masukkan 6 digit kode.');
      return;
    }

    FocusScope.of(context).unfocus();
    setState(() {
      _verifying = true;
      _error = null;
    });

    try {
      await widget.auth.verifyEmail(code);
      if (mounted) showAppMessage(context, 'Email berhasil diverifikasi.');
    } on ApiException catch (e) {
      if (mounted) setState(() => _error = e.fieldError('code') ?? e.message);
    } finally {
      if (mounted) setState(() => _verifying = false);
    }
  }

  Future<void> _resend() async {
    setState(() {
      _resending = true;
      _error = null;
    });

    try {
      await widget.auth.resendCode();
      if (!mounted) return;
      _code.clear();
      _syncCooldown();
      showAppMessage(
        context,
        'Kode baru dikirim ke ${widget.auth.user?.email}.',
      );
    } on ApiException catch (e) {
      if (mounted) setState(() => _error = e.fieldError('email') ?? e.message);
    } finally {
      if (mounted) setState(() => _resending = false);
    }
  }

  Future<void> _logout() async {
    final ok = await confirmDialog(
      context,
      title: 'Keluar',
      message: 'Yakin ingin keluar dari akun ini?',
      confirmLabel: 'Ya, keluar',
      destructive: true,
    );
    if (ok) await widget.auth.logout();
  }

  @override
  Widget build(BuildContext context) {
    final email = widget.auth.user?.email ?? '';
    final canResend = _cooldown == 0 && !_resending && !_verifying;

    return AuthScaffold(
      title: 'Verifikasi email',
      subtitle:
          'Masukkan kode 6 digit yang kami kirim ke $email. Kode berlaku 10 menit.',
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          if (_error != null) ...[
            ErrorBanner(_error!),
            const SizedBox(height: 14),
          ],
          AppTextField(
            controller: _code,
            label: 'Kode verifikasi',
            otp: true,
            otpLength: 6,
            enabled: !_verifying,
            inputFormatters: [FilteringTextInputFormatter.digitsOnly],
            onOtpCompleted: (_) {
              if (!_verifying) _verify();
            },
            onSubmitted: (_) => _verify(),
          ),
          const SizedBox(height: 24),
          PrimaryButton(
            label: 'Verifikasi',
            loading: _verifying,
            onPressed: _verify,
          ),
          const SizedBox(height: 10),
          TextButton(
            onPressed: canResend ? _resend : null,
            child: Text(
              _resending
                  ? 'Mengirim...'
                  : _cooldown > 0
                  ? 'Kirim ulang kode dalam $_cooldown dtk'
                  : 'Kirim ulang kode',
            ),
          ),
          TextButton(
            onPressed: _verifying ? null : _logout,
            style: TextButton.styleFrom(foregroundColor: AppColors.muted),
            child: const Text('Keluar'),
          ),
        ],
      ),
    );
  }
}
