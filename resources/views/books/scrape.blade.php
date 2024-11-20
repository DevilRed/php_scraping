@if(Session::has('notice'))
    <p class="alert alert-info">{{ Session::get('notice') }}</p>
@endif

@if(Session::has('alert'))
    <p class="alert alert-danger">{{ Session::get('alert') }}</p>
@endif

<a href="/">Go Back</a>
