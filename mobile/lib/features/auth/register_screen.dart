import 'package:flutter/material.dart';

import '../../core/api_exception.dart';
import '../../core/theme.dart';
import '../../core/validators.dart';
import '../../widgets/app_text_field.dart';
import '../../widgets/auth_scaffold.dart';
import '../../widgets/error_banner.dart';
import '../../widgets/primary_button.dart';
import 'auth_controller.dart';

class RegisterScreen extends StatefulWidget {
  const RegisterScreen({super.key, required this.auth, required this.onLogin});

  final AuthController auth;
  final VoidCallback onLogin;

  @override
  State<RegisterScreen> createState() => _RegisterScreenState();
}

class _RegisterScreenState extends State<RegisterScreen> {
  final _formKey = GlobalKey<FormState>();
  final _name = TextEditingController();
  final _email = TextEditingController();
  final _password = TextEditingController();
  final _confirm = TextEditingController();
  bool _loading = false;
  String? _error;

  @override
  void dispose() {
    _name.dispose();
    _email.dispose();
    _password.dispose();
    _confirm.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    FocusScope.of(context).unfocus();
    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      await widget.auth.register(
        name: _name.text.trim(),
        email: _email.text.trim(),
        password: _password.text,
        passwordConfirmation: _confirm.text,
      );
    } on ApiException catch (e) {
      if (mounted) {
        setState(() {
          _error = e.fieldErrors.isEmpty
              ? e.message
              : e.fieldErrors.values.expand((l) => l).join('\n');
        });
      }
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return AuthScaffold(
      title: 'Daftar',
      subtitle: 'Kami akan mengirim kode verifikasi 6 digit ke email Anda.',
      child: Form(
        key: _formKey,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            if (_error != null) ...[
              ErrorBanner(_error!),
              const SizedBox(height: 14),
            ],
            AppTextField(
              controller: _name,
              label: 'Nama lengkap',
              textInputAction: TextInputAction.next,
              textCapitalization: TextCapitalization.words,
              autofillHints: const [AutofillHints.name],
              validator: (v) => Validators.required(v, 'Nama'),
              prefixIcon: const Icon(
                Icons.person_outline_rounded,
                size: 20,
                color: AppColors.muted,
              ),
            ),
            const SizedBox(height: 16),
            AppTextField(
              controller: _email,
              label: 'Email',
              keyboardType: TextInputType.emailAddress,
              textInputAction: TextInputAction.next,
              autofillHints: const [AutofillHints.email],
              validator: Validators.email,
              prefixIcon: const Icon(
                Icons.alternate_email_rounded,
                size: 20,
                color: AppColors.muted,
              ),
            ),
            const SizedBox(height: 16),
            AppTextField(
              controller: _password,
              label: 'Password',
              hint: 'Minimal 8 karakter',
              obscure: true,
              textInputAction: TextInputAction.next,
              autofillHints: const [AutofillHints.newPassword],
              validator: Validators.newPassword,
              prefixIcon: const Icon(
                Icons.lock_outline_rounded,
                size: 20,
                color: AppColors.muted,
              ),
            ),
            const SizedBox(height: 16),
            AppTextField(
              controller: _confirm,
              label: 'Ulangi password',
              obscure: true,
              textInputAction: TextInputAction.done,
              validator: Validators.sameAs(() => _password.text),
              onSubmitted: (_) => _submit(),
              prefixIcon: const Icon(
                Icons.lock_reset_rounded,
                size: 20,
                color: AppColors.muted,
              ),
            ),
            const SizedBox(height: 22),
            PrimaryButton(
              label: 'Daftar',
              loading: _loading,
              onPressed: _submit,
            ),
            const SizedBox(height: 10),
            TextButton(
              onPressed: _loading ? null : widget.onLogin,
              child: const Text('Sudah punya akun? Masuk'),
            ),
          ],
        ),
      ),
    );
  }
}
