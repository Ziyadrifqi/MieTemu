class User {
  const User({
    required this.id,
    required this.name,
    required this.email,
    required this.emailVerified,
  });

  final int id;
  final String name;
  final String email;
  final bool emailVerified;

  factory User.fromJson(Map<String, dynamic> json) => User(
    id: json['id'] as int,
    name: json['name'] as String,
    email: json['email'] as String,
    emailVerified: json['email_verified'] == true,
  );
}
