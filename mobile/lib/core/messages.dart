import 'package:flutter/material.dart';

import 'theme.dart';
import 'time.dart';

/// Alert berwaktu WIB. Error tampil lebih lama dari pesan sukses.
void showAppMessage(
  BuildContext context,
  String message, {
  bool error = false,
}) {
  final messenger = ScaffoldMessenger.of(context);
  messenger
    ..hideCurrentSnackBar()
    ..showSnackBar(
      SnackBar(
        behavior: SnackBarBehavior.floating,
        backgroundColor: error ? AppColors.danger : AppColors.success,
        duration: Duration(seconds: error ? 8 : 5),
        showCloseIcon: true,
        closeIconColor: Colors.white,
        content: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Expanded(
              child: Text(message, style: const TextStyle(color: Colors.white)),
            ),
            const SizedBox(width: 12),
            Text(
              '${formatWibClock(DateTime.now())} WIB',
              style: const TextStyle(color: Colors.white70, fontSize: 12),
            ),
          ],
        ),
      ),
    );
}
