import 'dart:async';

import 'package:flutter/material.dart';

import '../../core/api_exception.dart';
import '../../core/config.dart';
import '../../core/messages.dart';
import '../../core/theme.dart';
import '../../core/validators.dart';
import '../../widgets/app_text_field.dart';
import '../../widgets/auth_scaffold.dart';
import '../../widgets/error_banner.dart';
import '../../widgets/primary_button.dart';
import 'auth_controller.dart';

class ForgotPasswordScreen extends StatefulWidget {
  const ForgotPasswordScreen({
    super.key,
    required this.auth,
    required this.onBack,
  });

  final AuthController auth;
  final VoidCallback onBack;

  @override
  State<ForgotPasswordScreen> createState() => _ForgotPasswordScreenState();
}

class _ForgotPasswordScreenState extends State<ForgotPasswordScreen> {
  final _formKey = GlobalKey<FormState>();
  final _email = TextEditingController();
  Timer? _timer;
  bool _loading = false;
  String? _error;
  String? _sentTo;
  DateTime? _sentAt;
  int _cooldown = 0;

  @override
  void initState() {
    super.initState();
    _timer = Timer.periodic(const Duration(seconds: 1), (_) => _syncCooldown());
  }

  @override
  void dispose() {
    _timer?.cancel();
    _email.dispose();
    super.dispose();
  }

  void _syncCooldown() {
    final sentAt = _sentAt;
    final remaining = sentAt == null
        ? 0
        : AppConfig.resendCooldownSeconds -
              DateTime.now().difference(sentAt).inSeconds;
    final next = remaining < 0 ? 0 : remaining;

    if (next != _cooldown && mounted) setState(() => _cooldown = next);
  }

  Future<void> _send({bool resend = false}) async {
    if (!resend && !_formKey.currentState!.validate()) return;

    FocusScope.of(context).unfocus();
    final email = _email.text.trim();
    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      await widget.auth.requestPasswordReset(email);
      if (!mounted) return;
      setState(() {
        _sentTo = email;
        _sentAt = DateTime.now();
      });
      _syncCooldown();
      showAppMessage(context, 'Permintaan reset password dikirim.');
    } on ApiException catch (e) {
      if (mounted) {
        setState(() => _error = e.fieldError('email') ?? e.message);
      }
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final sentTo = _sentTo;

    return AuthScaffold(
      title: sentTo == null ? 'Lupa password' : 'Cek email Anda',
      subtitle: sentTo == null
          ? 'Masukkan email akun Anda. Kami akan mengirim tautan untuk membuat password baru.'
          : 'Jika $sentTo terdaftar, tautan reset password sudah dikirim.',
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          if (_error != null) ...[
            ErrorBanner(_error!),
            const SizedBox(height: 14),
          ],
          if (sentTo == null) _buildForm() else _buildSent(),
        ],
      ),
    );
  }

  Widget _buildForm() {
    return Form(
      key: _formKey,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          AppTextField(
            controller: _email,
            label: 'Email',
            keyboardType: TextInputType.emailAddress,
            textInputAction: TextInputAction.done,
            autofillHints: const [AutofillHints.email],
            validator: Validators.email,
            onSubmitted: (_) => _send(),
          ),
          const SizedBox(height: 20),
          PrimaryButton(
            label: 'Kirim tautan reset',
            loading: _loading,
            onPressed: _send,
          ),
          const SizedBox(height: 8),
          TextButton(
            onPressed: _loading ? null : widget.onBack,
            child: const Text('Kembali ke Masuk'),
          ),
        ],
      ),
    );
  }

  Widget _buildSent() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Container(
          padding: const EdgeInsets.all(14),
          decoration: BoxDecoration(
            color: AppColors.soft,
            borderRadius: BorderRadius.circular(12),
            border: Border.all(color: AppColors.line),
          ),
          child: const Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Icon(Icons.mark_email_read_outlined, color: AppColors.red),
                  SizedBox(width: 8),
                  Text(
                    'Langkah selanjutnya',
                    style: TextStyle(fontWeight: FontWeight.w700),
                  ),
                ],
              ),
              SizedBox(height: 10),
              Text(
                '1. Buka email dan ketuk tombol Reset Password.\n'
                '2. Buat password baru di halaman yang terbuka.\n'
                '3. Kembali ke aplikasi, lalu Masuk dengan password baru.',
                style: TextStyle(height: 1.6),
              ),
            ],
          ),
        ),
        const SizedBox(height: 20),
        PrimaryButton(label: 'Kembali ke Masuk', onPressed: widget.onBack),
        const SizedBox(height: 8),
        TextButton(
          onPressed: (_cooldown == 0 && !_loading)
              ? () => _send(resend: true)
              : null,
          child: Text(
            _loading
                ? 'Mengirim...'
                : _cooldown > 0
                ? 'Kirim ulang email dalam $_cooldown dtk'
                : 'Kirim ulang email',
          ),
        ),
        TextButton(
          onPressed: _loading ? null : () => setState(() => _sentTo = null),
          style: TextButton.styleFrom(foregroundColor: AppColors.muted),
          child: const Text('Salah email? Ubah'),
        ),
      ],
    );
  }
}
