import 'package:equatable/equatable.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:mohish/core/data/api_client.dart';
import 'package:mohish/features/referral/data/referral_repository.dart';
import 'package:mohish/features/referral/domain/referral_item.dart';

class ReferralState extends Equatable {
  const ReferralState({
    this.loading = false,
    this.stats,
    this.errorMessage,
    this.message,
  });

  final bool loading;
  final ReferralStats? stats;
  final String? errorMessage;
  final String? message;

  ReferralState copyWith({
    bool? loading,
    ReferralStats? stats,
    String? errorMessage,
    String? message,
  }) {
    return ReferralState(
      loading: loading ?? this.loading,
      stats: stats ?? this.stats,
      errorMessage: errorMessage,
      message: message,
    );
  }

  @override
  List<Object?> get props => [loading, stats, errorMessage, message];
}

class ReferralCubit extends Cubit<ReferralState> {
  ReferralCubit(this._repository) : super(const ReferralState());

  final ReferralRepository _repository;

  Future<void> load() async {
    emit(state.copyWith(loading: true, errorMessage: null));
    try {
      final stats = await _repository.fetchReferrals();
      emit(state.copyWith(loading: false, stats: stats));
    } on ApiException catch (exception) {
      emit(state.copyWith(loading: false, errorMessage: exception.message));
    }
  }

  Future<void> applyCode(String code) async {
    emit(state.copyWith(loading: true, errorMessage: null, message: null));
    try {
      await _repository.applyCode(code);
      await load();
      emit(state.copyWith(message: 'Referral code applied successfully.'));
    } on ApiException catch (exception) {
      emit(state.copyWith(loading: false, errorMessage: exception.message));
    }
  }
}
