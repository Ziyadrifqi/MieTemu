import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../core/theme.dart';

class AppTextField extends StatefulWidget {
  const AppTextField({
    super.key,
    required this.controller,
    required this.label,
    this.hint,
    this.keyboardType,
    this.textInputAction,
    this.obscure = false,
    this.validator,
    this.autofillHints,
    this.onSubmitted,
    this.onChanged,
    this.inputFormatters,
    this.maxLength,
    this.textAlign = TextAlign.start,
    this.style,
    this.textCapitalization = TextCapitalization.none,
    this.enabled = true,
    this.prefixIcon,
    this.otp = false,
    this.otpLength = 6,
    this.onOtpCompleted,
  });

  final TextEditingController controller;
  final String label;
  final String? hint;
  final TextInputType? keyboardType;
  final TextInputAction? textInputAction;
  final bool obscure;
  final String? Function(String?)? validator;
  final Iterable<String>? autofillHints;
  final ValueChanged<String>? onSubmitted;
  final ValueChanged<String>? onChanged;
  final List<TextInputFormatter>? inputFormatters;
  final int? maxLength;
  final TextAlign textAlign;
  final TextStyle? style;
  final TextCapitalization textCapitalization;
  final bool enabled;
  final Widget? prefixIcon;

  final bool otp;
  final int otpLength;
  final ValueChanged<String>? onOtpCompleted;

  @override
  State<AppTextField> createState() => _AppTextFieldState();
}

class _AppTextFieldState extends State<AppTextField> {
  late bool _hidden = widget.obscure;
  final FocusNode _otpFocus = FocusNode();

  @override
  void initState() {
    super.initState();
    if (widget.otp) {
      widget.controller.addListener(_onOtpChanged);
      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (mounted && widget.enabled) _otpFocus.requestFocus();
      });
    }
  }

  @override
  void dispose() {
    if (widget.otp) {
      widget.controller.removeListener(_onOtpChanged);
    }
    _otpFocus.dispose();
    super.dispose();
  }

  void _onOtpChanged() {
    if (!mounted) return;
    setState(() {});
    final text = widget.controller.text;
    if (text.length == widget.otpLength && widget.onOtpCompleted != null) {
      widget.onOtpCompleted!(text);
    }
  }

  OutlineInputBorder _border(Color color, {double width = 1.2}) =>
      OutlineInputBorder(
        borderRadius: BorderRadius.circular(16),
        borderSide: BorderSide(color: color, width: width),
      );

  @override
  Widget build(BuildContext context) {
    if (widget.otp) return _buildOtp();
    return _buildRegular();
  }

  // ============ MODE OTP ============
  Widget _buildOtp() {
    final text = widget.controller.text;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Text(
          widget.label,
          style: const TextStyle(
            fontSize: 13,
            fontWeight: FontWeight.w600,
            color: AppColors.muted,
          ),
        ),
        const SizedBox(height: 10),
        GestureDetector(
          onTap: widget.enabled ? () => _otpFocus.requestFocus() : null,
          child: Stack(
            alignment: Alignment.center,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: List.generate(widget.otpLength, (i) {
                  final hasChar = i < text.length;
                  final char = hasChar ? text[i] : '';
                  final isActive = widget.enabled && i == text.length;
                  return AnimatedContainer(
                    duration: const Duration(milliseconds: 180),
                    width: 46,
                    height: 58,
                    alignment: Alignment.center,
                    decoration: BoxDecoration(
                      color: AppColors.soft,
                      borderRadius: BorderRadius.circular(14),
                      border: Border.all(
                        color: isActive ? AppColors.red : AppColors.line,
                        width: isActive ? 1.8 : 1,
                      ),
                      boxShadow: isActive ? AppColors.softShadow : [],
                    ),
                    child: Text(
                      char,
                      style: const TextStyle(
                        fontSize: 22,
                        fontWeight: FontWeight.w700,
                        color: AppColors.ink,
                      ),
                    ),
                  );
                }),
              ),
              Positioned.fill(
                child: Opacity(
                  opacity: 0,
                  child: TextField(
                    controller: widget.controller,
                    focusNode: _otpFocus,
                    enabled: widget.enabled,
                    keyboardType: TextInputType.number,
                    inputFormatters:
                        widget.inputFormatters ??
                        [FilteringTextInputFormatter.digitsOnly],
                    maxLength: widget.otpLength,
                    showCursor: false,
                    autofillHints: widget.autofillHints,
                    onSubmitted: widget.onSubmitted,
                    decoration: const InputDecoration(
                      counterText: '',
                      border: InputBorder.none,
                    ),
                  ),
                ),
              ),
            ],
          ),
        ),
        if (widget.hint != null) ...[
          const SizedBox(height: 8),
          Text(
            widget.hint!,
            style: const TextStyle(fontSize: 12.5, color: AppColors.muted),
          ),
        ],
      ],
    );
  }

  // ============ MODE REGULAR ============
  Widget _buildRegular() {
    return TextFormField(
      controller: widget.controller,
      enabled: widget.enabled,
      keyboardType: widget.keyboardType,
      textInputAction: widget.textInputAction,
      obscureText: _hidden,
      autofillHints: widget.autofillHints,
      validator: widget.validator,
      onFieldSubmitted: widget.onSubmitted,
      onChanged: widget.onChanged,
      inputFormatters: widget.inputFormatters,
      maxLength: widget.maxLength,
      textAlign: widget.textAlign,
      style: widget.style,
      textCapitalization: widget.textCapitalization,
      autovalidateMode: AutovalidateMode.onUserInteraction,
      decoration: InputDecoration(
        labelText: widget.label,
        hintText: widget.hint,
        counterText: '',
        filled: true,
        fillColor: Colors.white,
        contentPadding: const EdgeInsets.symmetric(
          horizontal: 18,
          vertical: 18,
        ),
        prefixIcon: widget.prefixIcon,
        border: _border(AppColors.line),
        enabledBorder: _border(AppColors.line),
        focusedBorder: _border(AppColors.red, width: 1.8),
        errorBorder: _border(AppColors.danger),
        focusedErrorBorder: _border(AppColors.danger, width: 1.8),
        suffixIcon: widget.obscure
            ? IconButton(
                tooltip: _hidden
                    ? 'Tampilkan password'
                    : 'Sembunyikan password',
                onPressed: () => setState(() => _hidden = !_hidden),
                icon: Icon(
                  _hidden
                      ? Icons.visibility_outlined
                      : Icons.visibility_off_outlined,
                  size: 20,
                  color: AppColors.muted,
                ),
              )
            : null,
      ),
    );
  }
}
