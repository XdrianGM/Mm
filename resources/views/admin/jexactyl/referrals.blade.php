{{-- Pterodactyl CHINA - Panel --}}
{{-- Simplified Chinese Translation Copyright (c) 2018 - 2022 ValiantShishu <vlssu@vlssu.com> --}}

{{-- This software is licensed under the terms of the MIT license. --}}
{{-- https://opensource.org/licenses/MIT --}}
@extends('layouts.admin')
@include('partials/admin.jexactyl.nav', ['activeTab' => 'referrals'])

@section('title')
    推荐系统
@endsection

@section('content-header')
    <h1>推荐系统<small>允许用户将其他人推荐给面板以赚取佣金。</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">管理</a></li>
        <li class="active">Jexactyl</li>
    </ol>
@endsection

@section('content')
    @yield('jexactyl::nav')
    <div class="row">
        <div class="col-xs-12">
        <form action="{{ route('admin.jexactyl.referrals') }}" method="POST">
                <div class="box
                    @if($enabled == 'true')
                        box-success
                    @else
                        box-danger
                    @endif
                ">
                    <div class="box-header with-border">
                        <h3 class="box-title">Referrals <small>Allow users to refer others to the Panel.</small></h3>
                    </div>
                    <div class="box-body">
                        <div class="row">
                            <div class="form-group col-md-4">
                                <label class="control-label">Referral System</label>
                                <div>
                                    <select name="enabled" class="form-control">
                                        <option @if ($enabled == 'false') selected @endif value="false">Disabled</option>
                                        <option @if ($enabled == 'true') selected @endif value="true">Enabled</option>
                                    </select>
                                    <p class="text-muted"><small>Determines whether users can refer others.</small></p>
                                </div>
                            </div>
                            <div class="form-group col-md-4">
                                <label class="control-label">Reward per referral</label>
                                <div>
                                    <div class="input-group">
                                        <input type="text" id="reward" name="reward" class="form-control" value="{{ $reward }}" />
                                        <span class="input-group-addon">credits</span>
                                    </div>
                                    <p class="text-muted"><small>The amount of credits to give users when they refer someone, and when someone uses a referral code.</small></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="box-footer">
                    {!! csrf_field() !!}
                    <button type="submit" name="_method" value="PATCH" class="btn btn-sm btn-primary pull-right">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
@endsection
