class DashboardStats {
  const DashboardStats({
    required this.totalPoints,
    required this.todayPoints,
    required this.adsWatchedToday,
    required this.weeklyPoints,
  });

  final int totalPoints;
  final int todayPoints;
  final int adsWatchedToday;
  final List<int> weeklyPoints;

  factory DashboardStats.fromJson(Map<String, dynamic> json) {
    return DashboardStats(
      totalPoints: (json['total_points'] as num?)?.toInt() ?? 0,
      todayPoints: (json['today_points'] as num?)?.toInt() ?? 0,
      adsWatchedToday: (json['ads_watched_today'] as num?)?.toInt() ?? 0,
      weeklyPoints:
          ((json['weekly_points'] as List<dynamic>? ?? <dynamic>[]).map(
            (e) => (e as num).toInt(),
          )).toList(),
    );
  }
}
