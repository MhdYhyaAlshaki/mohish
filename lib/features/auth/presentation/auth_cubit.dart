import 'package:equatable/equatable.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:mohish/core/data/api_client.dart';
import 'package:mohish/features/auth/data/auth_repository.dart';
import 'package:mohish/features/auth/domain/auth_user.dart';

enum AuthStatus { loading, unauthenticated, authenticated }

class AuthState extends Equatable {
  const AuthState({required this.status, this.user, this.errorMessage});

  const AuthState.loading() : this(status: AuthStatus.loading);
  const AuthState.unauthenticated() : this(status: AuthStatus.unauthenticated);
  const AuthState.authenticated(AuthUser user)
    : this(status: AuthStatus.authenticated, user: user);

  final AuthStatus status;
  final AuthUser? user;
  final String? errorMessage;

  AuthState copyWith({
    AuthStatus? status,
    AuthUser? user,
    String? errorMessage,
  }) {
    return AuthState(
      status: status ?? this.status,
      user: user ?? this.user,
      errorMessage: errorMessage,
    );
  }

  @override
  List<Object?> get props => [status, user, errorMessage];
}

class AuthCubit extends Cubit<AuthState> {
  AuthCubit(this._repository) : super(const AuthState.loading());

  final AuthRepository _repository;

  Future<void> bootstrap() async {
    emit(const AuthState.loading());
    try {
      final token = await _repository.restoreToken();
      if (token == null) {
        emit(const AuthState.unauthenticated());
        return;
      }
      final user = await _repository.profile();
      emit(AuthState.authenticated(user));
    } on ApiException {
      await _repository.logout();
      emit(const AuthState.unauthenticated());
    }
  }

  Future<void> login(String email, String password) async {
    emit(state.copyWith(status: AuthStatus.loading, errorMessage: null));
    try {
      final result = await _repository.login(email: email, password: password);
      emit(AuthState.authenticated(result.user));
    } on ApiException catch (exception) {
      emit(
        const AuthState.unauthenticated().copyWith(
          errorMessage: exception.message,
        ),
      );
    }
  }

  Future<void> register(String name, String email, String password) async {
    emit(state.copyWith(status: AuthStatus.loading, errorMessage: null));
    try {
      final result = await _repository.register(
        name: name,
        email: email,
        password: password,
      );
      emit(AuthState.authenticated(result.user));
    } on ApiException catch (exception) {
      emit(
        const AuthState.unauthenticated().copyWith(
          errorMessage: exception.message,
        ),
      );
    }
  }

  Future<void> refreshProfile() async {
    if (state.status != AuthStatus.authenticated) return;
    try {
      final user = await _repository.profile();
      emit(AuthState.authenticated(user));
    } on ApiException catch (_) {}
  }

  Future<void> logout() async {
    await _repository.logout();
    emit(const AuthState.unauthenticated());
  }
}
