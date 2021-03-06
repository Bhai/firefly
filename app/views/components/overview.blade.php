@extends('layouts.default')
@section('breadcrumbs', Breadcrumbs::render('componentoverview',$component,null))
@section('content')
<div class="row">
    <div class="col-lg-12">
        <h2>General overview for {{$component->type->type}} "{{{$component->name}}}"</h2>
        @if($parent)
            <h3>Child of {{{$parent->name}}}</h3>
        @endif
        <div class="btn-group">
            <a href="{{URL::Route('editcomponent',$component->id)}}" class="btn btn-default"><span
                    class="glyphicon glyphicon-pencil"></span></a> <a
                href="{{URL::Route('deletecomponent',$component->id)}}" class="btn btn-default btn-danger"><span
                    class="glyphicon glyphicon-trash"></span></a>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-lg-12">
        <h4>Months</h4>
        <table class="table table-bordered table-striped">
            <tr>
                <th>Month</th>
                <th>Total transactions / transfers</th>
                <th colspan="2">Limit</th>
                <th>Total amount</th>
            </tr>
            @foreach($months as $m)
            <tr>
                <td><a href="{{$m['url']}}" title="{{$m['month']}}">{{{$m['title']}}}</a></td>
                <td>{{$m['count']}}</td>
                @if(isset($m['limit']))
                <td>{{mf($m['limit'],false,true)}}</td>
                <td>
                    <div class="btn-group">
                        <a data-toggle="modal" data-target="#PopupModal" href="{{URL::Route('editcomponentlimit',[$m['limit-id']])}}" class="btn btn-info btn-default btn-xs"><span class="glyphicon glyphicon-pencil"></span></a>
                        <a data-toggle="modal" data-target="#PopupModal" href="{{URL::Route('deletecomponentlimit',[$m['limit-id']])}}" class="btn btn-default btn-danger btn-xs"><span class="glyphicon glyphicon-trash"></span></a>
                    </div>
                </td>
                @else
                    <td colspan="2"><a data-toggle="modal" href="{{URL::Route('addcomponentlimit',[$component->id,$m['year'],$m['month']])}}" data-target="#PopupModal" class="btn btn-default btn-xs"><span class="glyphicon glyphicon-plus-sign"></span></a></td>
                @endif
                @if(isset($m['limit']) && ($m['sum']*-1) > $m['limit'])
                    <td class="danger">{{mf($m['sum'],false,true)}}</td>
                @else
                    <td>{{mf($m['sum'],false,true)}}</td>
                @endif
            </tr>
            @endforeach
        </table>
    </div>
</div>
@stop
