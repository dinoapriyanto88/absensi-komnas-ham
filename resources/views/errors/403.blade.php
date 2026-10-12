@extends('errors.minimal')
@section('code', '403')
@section('title', 'Akses Ditolak')
@section('message')
@php($pesan = $exception->getMessage())
{{ ($pesan === '' || $pesan === 'This action is unauthorized.') ? 'Anda tidak memiliki hak akses ke halaman ini.' : $pesan }}
@endsection
