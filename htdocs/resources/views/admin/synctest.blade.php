@extends('layouts.syncboard')

@section('page_heading')
國小學程資料連線測試中心
@endsection

@section('section')
<div class="container">
	<div class="row">
	@if (session('error'))
	    <div class="alert alert-danger">
		{{ session('error') }}
	    </div>
	@endif
	@if (session('success'))
	    <div class="alert alert-success">
		{{ session('success') }}
	    </div>
	@endif
	<div class="col-sm-12">
		<div class="panel panel-default">	  
		<div class="panel-heading">
			<h4>scope: 學校資訊</h4>
		</div>
		<div class="panel-body">
			<div class="form-group custom-search-form">
				<select id="field" name="field" class="form-control">
					<option value="school_info" {{ $my_field == 'school_info' ? 'selected' : '' }}>學校基本資料</option>
					<option value="department_info" {{ $my_field == 'department_info' ? 'selected' : '' }}>處室聯絡人資訊</option>
					<option value="classes_info" {{ $my_field == 'classes_info' ? 'selected' : '' }}>班級資訊</option>
					<option value="special_info" {{ $my_field == 'special_info' ? 'selected' : '' }}>特殊教育概況</option>
				</select>
				<label>學校統一編號：</label>
				<input type="text" class="form-control" id="sid" name="sid" value="{{ old('sid') }}" required>
				<span class="input-group-btn">
					<button class="btn btn-default" type="button" onclick="location='{{ url()->current() }}?field=' + $('#field').val() + '&sid=' + $('#sid').val();">
						傳送請求
					</button>
				</span>
			</div>
		</div>
    	</div>
	</div>
	<div class="col-sm-12">
		<div class="panel panel-default">
		<div class="panel-heading">
			<h4>測試結果</h4>
		</div>
		<div class="panel-body">
			<table class="table table-hover">
				<tbody>
					<tr>
						<td id="viewport" style="word-wrap: break-word">
						@if ($result)
						{{ var_dump($result) }}
						@endif
						</td>
					</tr>
				</tbody>
			</table>
		</div>
		</div>
	</div>
	</div>
</div>
@endsection