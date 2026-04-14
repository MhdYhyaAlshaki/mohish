class AdStartResult {
  const AdStartResult({
    required this.sessionId,
    required this.expiresAt,
    required this.cooldownSeconds,
    required this.remainingToday,
  });

  final String sessionId;
  final DateTime expiresAt;
  final int cooldownSeconds;
  final int remainingToday;

  factory AdStartResult.fromJson(Map<String, dynamic> json) {
    return AdStartResult(
      sessionId: json['session_id'] as String,
      expiresAt: DateTime.parse(json['expires_at'] as String),
      cooldownSeconds: (json['cooldown_seconds'] as num?)?.toInt() ?? 30,
      remainingToday: (json['remaining_today'] as num?)?.toInt() ?? 0,
    );
  }
}

class AdCompleteResult {
  const AdCompleteResult({
    required this.awardedPoints,
    required this.newBalance,
    required this.dailyCount,
    required this.nextAvailableAt,
  });

  final int awardedPoints;
  final int newBalance;
  final int dailyCount;
  final DateTime nextAvailableAt;

  factory AdCompleteResult.fromJson(Map<String, dynamic> json) {
    return AdCompleteResult(
      awardedPoints: (json['awarded_points'] as num?)?.toInt() ?? 0,
      newBalance: (json['new_balance'] as num?)?.toInt() ?? 0,
      dailyCount: (json['daily_count'] as num?)?.toInt() ?? 0,
      nextAvailableAt: DateTime.parse(json['next_available_at'] as String),
    );
  }
}
