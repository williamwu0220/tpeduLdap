@extends('layouts.syncboard')

@section('page_heading')
<h1 class="page-header">同步學生</h1>
@endsection

@section('section')
<div class="container">
	<div class="row">
	@if (isset($result))
	    <div class="alert alert-info">
	    @foreach ($result as $line)
		{{ $line }}<br>
		@endforeach
	    </div>
	@endif
	@if (isset($areas))
	<div class="col-sm-12">
		@if ($sims == 'alle')
		<form role="form" method="POST" action="{{ route('sync.ps.sync_student') }}">
		@endif
		@if ($sims == 'oneplus')
		<form role="form" method="POST" action="{{ route('sync.js.sync_student') }}">
		@endif
		@csrf
    	<div class="input-group custom-search-form">
			<select id="area" name='area' class="form-control" style="width: auto" onchange="location='{{ url()->current() }}?area=' + $(this).val();">
				@foreach ($areas as $st)
			    	<option value="{{ $st }}"{{ $area == $st ? ' selected' : '' }}>{{ $st }}</option>
			    @endforeach
			</select>
			<select name='dc' class="form-control" style="width: auto" onchange="location='{{ url()->current() }}?area=' + $('#area').val() + '&dc=' + $(this).val();">
				@foreach ($schools as $sch)
			    	<option value="{{ $sch->o }}"{{ $dc == $sch->o ? ' selected' : '' }}>{{ $sch->description }}</option>
			    @endforeach
			</select>
			<select name='cls' class="form-control" style="width: auto">
				@if (sizeof($classes) > 0)
					<option value="">全部班級</option>
				@endif
				@foreach ($classes as $class)
			    	<option value="{{ $class->clsid }}"{{ $cls == $class->clsid ? ' selected' : '' }}>{{ $class->clsname }}</option>
			    @endforeach
			</select>
            <span class="input-group-btn" style="width: auto">
            	<button class="btn btn-default" type="submit">
            		開始同步
            	</button>
        	</span>
    	</div>
		</form>
	</div>
	@else
	@if ($sims == 'alle')
	<form id="sync" role="form" method="POST" action="{{ route('sync.ps.sync_student') }}">
	@endif
	@if ($sims == 'oneplus')
	<form id="sync" role="form" method="POST" action="{{ route('sync.js.sync_student') }}">
	@endif
		@csrf
		<input type="hidden" name="area" value="{{ $area }}">
		<input type="hidden" name="dc" value="{{ $dc }}">
		<input type="hidden" name="clsid" value="{{ $clsid }}">
		<span class="input-group-btn" style="width: auto">
			<button class="btn btn-default" type="submit">
				繼續同步下一個班級：{{ $clsid }}
			</button>
		</span>
		</form>
		<script>setTimeout("$('#sync').submit()", 10000);</script>
	@endif
	</div>
</div>
@endsection
