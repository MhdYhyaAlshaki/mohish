class ReferralItem {
  const ReferralItem({
    required this.id,
    required this.earnings,
    required this.name,
  });

  final int id;
  final int earnings;
  final String name;

  factory ReferralItem.fromJson(Map<String, dynamic> json) {
    final user = json['referred_user'] as Map<String, dynamic>?;
    return ReferralItem(
      id: (json['id'] as num).toInt(),
      earnings: (json['earnings'] as num?)?.toInt() ?? 0,
      name: user?['name'] as String? ?? 'Unknown',
    );
  }
}

class ReferralStats {
  const ReferralStats({
    required this.referralCode,
    required this.totalEarnings,
    required this.items,
  });

  final String referralCode;
  final int totalEarnings;
  final List<ReferralItem> items;

  factory ReferralStats.fromJson(Map<String, dynamic> json) {
    return ReferralStats(
      referralCode: json['referral_code'] as String? ?? '',
      totalEarnings: (json['total_referral_earnings'] as num?)?.toInt() ?? 0,
      items: ((json['items'] as List<dynamic>? ?? <dynamic>[])
          .map((entry) => ReferralItem.fromJson(entry as Map<String, dynamic>))
          .toList()),
    );
  }
}
