/// Represents the structured ad response returned by
/// GET /api/v1/ad?placement=xxx&platform=android
class AdPlacementResponse {
  const AdPlacementResponse({
    required this.impressionId,
    required this.type,
    required this.network,
    required this.adUnitId,
    required this.placementKey,
    required this.refreshAfter,
    this.clickUrl,
    this.blocked = false,
    this.blockReason,
  });

  /// Null when the response came from a fallback (no campaign was matched).
  final int? impressionId;

  /// Placement type: banner | interstitial | rewarded | native | app_open
  final String type;

  /// Network slug: admob | meta | unity | applovin | direct
  final String network;

  /// The platform-specific ad unit ID chosen by the backend decision engine.
  /// If null the caller should use its own hardcoded fallback.
  final String? adUnitId;

  final String placementKey;

  /// How many seconds until the caller should request a fresh ad.
  final int refreshAfter;

  final String? clickUrl;

  /// True when the backend rejected the request (e.g. VPN detected).
  final bool blocked;
  final String? blockReason;

  factory AdPlacementResponse.fromJson(Map<String, dynamic> json) {
    return AdPlacementResponse(
      impressionId: json['impression_id'] as int?,
      type: json['type'] as String? ?? 'banner',
      network: json['network'] as String? ?? 'admob',
      adUnitId: json['ad_unit_id'] as String?,
      placementKey: json['placement_key'] as String? ?? '',
      refreshAfter: (json['refresh_after'] as num?)?.toInt() ?? 30,
      clickUrl: json['click_url'] as String?,
      blocked: json['blocked'] as bool? ?? false,
      blockReason: json['reason'] as String?,
    );
  }
}
