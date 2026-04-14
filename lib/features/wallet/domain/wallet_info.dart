class WalletInfo {
  const WalletInfo({required this.points, required this.referralCode});

  final int points;
  final String referralCode;

  factory WalletInfo.fromProfile(Map<String, dynamic> json) {
    return WalletInfo(
      points: (json['points'] as num?)?.toInt() ?? 0,
      referralCode: json['referral_code'] as String? ?? '',
    );
  }
}
