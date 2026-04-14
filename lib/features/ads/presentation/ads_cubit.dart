import 'dart:async';
import 'package:equatable/equatable.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:mohish/core/data/api_client.dart';
import 'package:mohish/core/data/rewarded_ad_service.dart';
import 'package:mohish/features/ads/data/ads_repository.dart';

enum RewardVisualStatus { idle, pending, confirmed }

class AdsState extends Equatable {
  const AdsState({
    this.loading = false,
    this.visualStatus = RewardVisualStatus.idle,
    this.lastAwarded = 0,
    this.balance = 0,
    this.dailyCount = 0,
    this.remainingToday = 12,
    this.cooldownRemaining = 0,
    this.message,
    this.errorMessage,
  });

  final bool loading;
  final RewardVisualStatus visualStatus;
  final int lastAwarded;
  final int balance;
  final int dailyCount;
  final int remainingToday;
  final int cooldownRemaining;
  final String? message;
  final String? errorMessage;

  bool get canWatch => !loading && cooldownRemaining <= 0 && remainingToday > 0;

  AdsState copyWith({
    bool? loading,
    RewardVisualStatus? visualStatus,
    int? lastAwarded,
    int? balance,
    int? dailyCount,
    int? remainingToday,
    int? cooldownRemaining,
    String? message,
    String? errorMessage,
  }) {
    return AdsState(
      loading: loading ?? this.loading,
      visualStatus: visualStatus ?? this.visualStatus,
      lastAwarded: lastAwarded ?? this.lastAwarded,
      balance: balance ?? this.balance,
      dailyCount: dailyCount ?? this.dailyCount,
      remainingToday: remainingToday ?? this.remainingToday,
      cooldownRemaining: cooldownRemaining ?? this.cooldownRemaining,
      message: message,
      errorMessage: errorMessage,
    );
  }

  @override
  List<Object?> get props => [
    loading,
    visualStatus,
    lastAwarded,
    balance,
    dailyCount,
    remainingToday,
    cooldownRemaining,
    message,
    errorMessage,
  ];
}

class AdsCubit extends Cubit<AdsState> {
  AdsCubit({
    required AdsRepository repository,
    required RewardedAdService adService,
  }) : _repository = repository,
       _adService = adService,
       super(const AdsState());

  final AdsRepository _repository;
  final RewardedAdService _adService;
  Timer? _cooldownTimer;

  Future<void> watchAndEarn() async {
    if (!state.canWatch) return;

    emit(
      state.copyWith(
        loading: true,
        visualStatus: RewardVisualStatus.pending,
        message: 'Watching ad...',
        errorMessage: null,
      ),
    );

    try {
      final start = await _repository.startAd();
      emit(state.copyWith(remainingToday: start.remainingToday));

      final completedAd = await _adService.showRewardedAd();
      if (!completedAd) {
        emit(
          state.copyWith(
            loading: false,
            visualStatus: RewardVisualStatus.idle,
            errorMessage: 'Ad did not complete.',
          ),
        );
        return;
      }

      final complete = await _repository.completeAd(start.sessionId);
      final cooldown = complete.nextAvailableAt
          .difference(DateTime.now())
          .inSeconds;

      emit(
        state.copyWith(
          loading: false,
          visualStatus: RewardVisualStatus.confirmed,
          lastAwarded: complete.awardedPoints,
          balance: complete.newBalance,
          dailyCount: complete.dailyCount,
          remainingToday: (start.remainingToday - 1).clamp(0, 999),
          cooldownRemaining: cooldown > 0 ? cooldown : 0,
          message: 'Reward confirmed.',
        ),
      );

      _startCooldownTicker();
    } on ApiException catch (exception) {
      emit(
        state.copyWith(
          loading: false,
          visualStatus: RewardVisualStatus.idle,
          errorMessage: exception.message,
          cooldownRemaining: exception.retryAfter ?? state.cooldownRemaining,
        ),
      );
      if (exception.retryAfter != null) {
        _startCooldownTicker();
      }
    }
  }

  void _startCooldownTicker() {
    _cooldownTimer?.cancel();
    _cooldownTimer = Timer.periodic(const Duration(seconds: 1), (timer) {
      if (state.cooldownRemaining <= 1) {
        timer.cancel();
        emit(
          state.copyWith(
            cooldownRemaining: 0,
            visualStatus: RewardVisualStatus.idle,
          ),
        );
      } else {
        emit(state.copyWith(cooldownRemaining: state.cooldownRemaining - 1));
      }
    });
  }

  @override
  Future<void> close() {
    _cooldownTimer?.cancel();
    return super.close();
  }
}
