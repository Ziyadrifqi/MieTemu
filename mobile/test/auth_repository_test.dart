import 'dart:convert';

import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';
import 'package:mietemu/core/api_client.dart';
import 'package:mietemu/core/token_storage.dart';
import 'package:mietemu/features/auth/auth_repository.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  test(
    'requestPasswordReset mengirim email ke endpoint forgot-password',
    () async {
      http.Request? captured;
      final api = ApiClient(
        tokenProvider: () async => null,
        client: MockClient((request) async {
          captured = request;
          return http.Response(
            jsonEncode({'message': 'ok'}),
            200,
            headers: {'content-type': 'application/json'},
          );
        }),
      );
      final repository = AuthRepository(api, TokenStorage());

      await repository.requestPasswordReset('budi@example.com');

      expect(captured!.method, 'POST');
      expect(captured!.url.path, endsWith('/auth/forgot-password'));
      expect(jsonDecode(captured!.body), {'email': 'budi@example.com'});
    },
  );
}
