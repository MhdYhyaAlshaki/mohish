import 'package:equatable/equatable.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:mohish/core/data/api_client.dart';
import 'package:mohish/features/dashboard/data/dashboard_repository.dart';
import 'package:mohish/features/dashboard/domain/dashboard_stats.dart';

class DashboardState extends Equatable {
  const DashboardState({this.loading = false, this.stats, this.errorMessage});

  final bool loading;
  final DashboardStats? stats;
  final String? errorMessage;

  DashboardState copyWith({
    bool? loading,
    DashboardStats? stats,
    String? errorMessage,
  }) {
    return DashboardState(
      loading: loading ?? this.loading,
      stats: stats ?? this.stats,
      errorMessage: errorMessage,
    );
  }

  @override
  List<Object?> get props => [loading, stats, errorMessage];
}

class DashboardCubit extends Cubit<DashboardState> {
  DashboardCubit(this._repository) : super(const DashboardState());

  final DashboardRepository _repository;

  Future<void> load() async {
    emit(state.copyWith(loading: true, errorMessage: null));
    try {
      final stats = await _repository.fetchDashboard();
      emit(state.copyWith(loading: false, stats: stats));
    } on ApiException catch (exception) {
      emit(state.copyWith(loading: false, errorMessage: exception.message));
    }
  }
}
