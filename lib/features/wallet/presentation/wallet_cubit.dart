import 'package:equatable/equatable.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:mohish/core/data/api_client.dart';
import 'package:mohish/features/wallet/data/wallet_repository.dart';
import 'package:mohish/features/wallet/domain/wallet_info.dart';

class WalletState extends Equatable {
  const WalletState({
    this.loading = false,
    this.wallet,
    this.message,
    this.errorMessage,
  });

  final bool loading;
  final WalletInfo? wallet;
  final String? message;
  final String? errorMessage;

  WalletState copyWith({
    bool? loading,
    WalletInfo? wallet,
    String? message,
    String? errorMessage,
  }) {
    return WalletState(
      loading: loading ?? this.loading,
      wallet: wallet ?? this.wallet,
      message: message,
      errorMessage: errorMessage,
    );
  }

  @override
  List<Object?> get props => [loading, wallet, message, errorMessage];
}

class WalletCubit extends Cubit<WalletState> {
  WalletCubit(this._repository) : super(const WalletState());

  final WalletRepository _repository;

  Future<void> load() async {
    emit(state.copyWith(loading: true, errorMessage: null, message: null));
    try {
      final wallet = await _repository.fetchWallet();
      emit(state.copyWith(loading: false, wallet: wallet));
    } on ApiException catch (exception) {
      emit(state.copyWith(loading: false, errorMessage: exception.message));
    }
  }

  Future<void> claimDailyReward() async {
    emit(state.copyWith(loading: true, errorMessage: null, message: null));
    try {
      final data = await _repository.claimReward();
      await load();
      emit(
        state.copyWith(
          loading: false,
          message: 'Claimed ${data['claimed_points']} points.',
        ),
      );
    } on ApiException catch (exception) {
      emit(state.copyWith(loading: false, errorMessage: exception.message));
    }
  }

  Future<void> requestWithdraw(int points) async {
    emit(state.copyWith(loading: true, errorMessage: null, message: null));
    try {
      await _repository.withdraw(points);
      await load();
      emit(state.copyWith(loading: false, message: 'Withdrawal requested.'));
    } on ApiException catch (exception) {
      emit(state.copyWith(loading: false, errorMessage: exception.message));
    }
  }
}
