import 'dart:convert';

import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';
import 'package:mietemu/core/api_client.dart';
import 'package:mietemu/core/api_exception.dart';
import 'package:mietemu/core/time.dart';

ApiClient clientReturning(
  int status,
  Map<String, dynamic> body, {
  String? token,
}) {
  return ApiClient(
    tokenProvider: () async => token,
    client: MockClient(
      (_) async => http.Response(
        jsonEncode(body),
        status,
        headers: {'content-type': 'application/json'},
      ),
    ),
  );
}

void main() {
  test('jam ditampilkan dalam WIB', () {
    expect(formatWibClock(DateTime.utc(2026, 10, 26, 3, 5, 9)), '10:05:09');
    expect(formatWibClock(DateTime.utc(2026, 10, 26, 20, 0, 0)), '03:00:00');
  });

  test('respons sukses dikembalikan apa adanya', () async {
    final api = clientReturning(200, {'token': 'abc'});
    expect(await api.post('/auth/login'), {'token': 'abc'});
  });

  test('422 menghasilkan pesan validasi dan error per kolom', () async {
    final api = clientReturning(422, {
      'message': 'x',
      'errors': {
        'email': ['Email atau password salah.'],
      },
    });

    await expectLater(
      api.post('/auth/login'),
      throwsA(
        isA<ApiException>()
            .having((e) => e.message, 'message', 'Email atau password salah.')
            .having((e) => e.statusCode, 'status', 422)
            .having(
              (e) => e.fieldError('email'),
              'email',
              'Email atau password salah.',
            ),
      ),
    );
  });

  test('429 tanpa detail memakai pesan bahasa Indonesia', () async {
    final api = clientReturning(429, {'message': 'Too Many Attempts.'});

    await expectLater(
      api.post('/auth/login'),
      throwsA(
        isA<ApiException>().having(
          (e) => e.message,
          'message',
          contains('Terlalu banyak'),
        ),
      ),
    );
  });

  test('401 dengan token memanggil onUnauthorized', () async {
    var called = false;
    final api = clientReturning(401, {
      'message': 'Unauthenticated.',
    }, token: 'old')..onUnauthorized = () => called = true;

    await expectLater(api.get('/me'), throwsA(isA<ApiException>()));
    expect(called, isTrue);
  });
}
