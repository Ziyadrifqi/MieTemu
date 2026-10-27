import 'dart:async';

import 'package:flutter/material.dart';

import 'app.dart';
import 'core/api_client.dart';
import 'core/token_storage.dart';
import 'features/auth/auth_controller.dart';
import 'features/auth/auth_repository.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();

  final tokens = TokenStorage();
  final api = ApiClient(tokenProvider: tokens.read);
  final auth = AuthController(AuthRepository(api, tokens));
  api.onUnauthorized = auth.handleUnauthorized;

  unawaited(auth.start());
  runApp(MieTemuApp(auth: auth));
}
