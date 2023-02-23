@extends('layouts.main');
@section('title', 'Upload Attachments')
@section('content')

    @livewire('user.jobs.attachments', ['jobslug' => $slug])
@endsection
