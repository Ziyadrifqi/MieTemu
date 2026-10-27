import 'dart:async';
import 'dart:convert';
import 'dart:io';

import 'package:http/http.dart' as http;

import 'api_exception.dart';
import 'config.dart';

typedef TokenProvider = Future<String?> Function();

/// Satu-satunya tempat yang berbicara HTTP ke API Laravel.
class ApiClient {
  ApiClient({required this._tokenProvider, http.Client? client})
    : _client = client ?? http.Client();

  final TokenProvider _tokenProvider;
  final http.Client _client;

  /// Dipanggil saat server menolak token (401), supaya sesi dibersihkan di satu tempat.
  void Function()? onUnauthorized;

  Future<Map<String, dynamic>> get(String path) => _send('GET', path);

  Future<Map<String, dynamic>> post(
    String path, {
    Map<String, dynamic>? body,
  }) => _send('POST', path, body: body);

  Future<Map<String, dynamic>> _send(
    String method,
    String path, {
    Map<String, dynamic>? body,
  }) async {
    final token = await _tokenProvider();
    final uri = Uri.parse('${AppConfig.apiBaseUrl}$path');
    final headers = <String, String>{
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      if (token != null) 'Authorization': 'Bearer $token',
    };

    final http.Response response;
    try {
      final request = method == 'GET'
          ? _client.get(uri, headers: headers)
          : _client.post(
              uri,
              headers: headers,
              body: jsonEncode(body ?? <String, dynamic>{}),
            );
      response = await request.timeout(AppConfig.requestTimeout);
    } on TimeoutException {
      throw const ApiException(
        'Server terlalu lama menjawab. Permintaan mungkin sudah diproses, coba Masuk atau ulangi sebentar lagi.',
      );
    } on SocketException {
      throw const ApiException.offline();
    } on http.ClientException {
      throw const ApiException.offline();
    }

    final data = _decode(response);

    if (response.statusCode >= 200 && response.statusCode < 300) return data;

    if (response.statusCode == 401 && token != null) onUnauthorized?.call();

    throw _toException(response.statusCode, data);
  }

  Map<String, dynamic> _decode(http.Response response) {
    if (response.bodyBytes.isEmpty) return {};
    try {
      final decoded = jsonDecode(utf8.decode(response.bodyBytes));
      return decoded is Map<String, dynamic> ? decoded : {};
    } on FormatException {
      return {}; // mis. halaman error HTML dari server
    }
  }

  ApiException _toException(int status, Map<String, dynamic> data) {
    final fieldErrors = <String, List<String>>{};
    final rawErrors = data['errors'];
    if (rawErrors is Map) {
      rawErrors.forEach((key, value) {
        if (value is List) {
          fieldErrors[key.toString()] = value.map((e) => e.toString()).toList();
        }
      });
    }

    final firstError = fieldErrors.values.expand((list) => list).firstOrNull;
    final serverMessage = data['message'] is String
        ? data['message'] as String
        : null;

    final message =
        firstError ??
        switch (status) {
          401 => 'Sesi Anda berakhir. Silakan masuk lagi.',
          429 => 'Terlalu banyak permintaan. Tunggu sebentar lalu coba lagi.',
          >= 500 => 'Terjadi kesalahan di server. Coba lagi nanti.',
          _ => serverMessage ?? 'Terjadi kesalahan.',
        };

    return ApiException(
      message,
      statusCode: status,
      code: data['code'] is String ? data['code'] as String : null,
      fieldErrors: fieldErrors,
    );
  }
}
