import 'package:flutter/material.dart';

import '../core/theme.dart';

class PrimaryButton extends StatefulWidget {
  const PrimaryButton({
    super.key,
    required this.label,
    required this.onPressed,
    this.loading = false,
  });

  final String label;
  final VoidCallback onPressed;
  final bool loading;

  @override
  State<PrimaryButton> createState() => _PrimaryButtonState();
}

class _PrimaryButtonState extends State<PrimaryButton> {
  bool _pressed = false;

  void _setPressed(bool value) {
    if (_pressed != value) setState(() => _pressed = value);
  }

  @override
  Widget build(BuildContext context) {
    final disabled = widget.loading;

    return Semantics(
      button: true,
      enabled: !disabled,
      label: widget.label,
      excludeSemantics: true,
      child: AnimatedScale(
        scale: _pressed ? 0.97 : 1.0,
        duration: const Duration(milliseconds: 120),
        child: GestureDetector(
          onTapDown: disabled ? null : (_) => _setPressed(true),
          onTapUp: disabled ? null : (_) => _setPressed(false),
          onTapCancel: disabled ? null : () => _setPressed(false),
          onTap: disabled
              ? null
              : widget.onPressed, // onTap = aksi ketuk untuk pembaca layar
          child: AnimatedContainer(
            duration: const Duration(milliseconds: 200),
            height: 54,
            decoration: BoxDecoration(
              gradient: disabled ? null : AppColors.brandGradient,
              color: disabled ? AppColors.red.withValues(alpha: 0.55) : null,
              borderRadius: BorderRadius.circular(16),
              boxShadow: disabled ? [] : AppColors.softShadow,
            ),
            alignment: Alignment.center,
            child: widget.loading
                ? const SizedBox(
                    width: 22,
                    height: 22,
                    child: CircularProgressIndicator(
                      strokeWidth: 2.4,
                      color: Colors.white,
                    ),
                  )
                : Text(
                    widget.label,
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 16,
                      fontWeight: FontWeight.w700,
                      letterSpacing: 0.2,
                    ),
                  ),
          ),
        ),
      ),
    );
  }
}
