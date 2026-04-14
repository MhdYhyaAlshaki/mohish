import 'package:equatable/equatable.dart';

class AuthUser extends Equatable {
  const AuthUser({
    required this.id,
    required this.name,
    required this.email,
    required this.points,
    required this.referralCode,
  });

  final int id;
  final String name;
  final String email;
  final int points;
  final String referralCode;

  factory AuthUser.fromJson(Map<String, dynamic> json) {
    return AuthUser(
      id: (json['id'] as num).toInt(),
      name: json['name'] as String,
      email: json['email'] as String,
      points: (json['points'] as num?)?.toInt() ?? 0,
      referralCode: json['referral_code'] as String? ?? '',
    );
  }

  @override
  List<Object?> get props => [id, name, email, points, referralCode];
}
