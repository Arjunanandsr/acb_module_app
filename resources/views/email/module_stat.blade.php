<!DOCTYPE html>
    <html lang="en-US">
      <head>
        <meta charset="utf-8">
      </head>
      <body>
      <h1>Hi,</h1>
        <p>Module file update status as follows:</p>
        <p>{{ $subject }}<p>
        <p>{{ $body }}</p>
        @if (count($error_messages) > 0)
        @foreach ($error_messages as $err)
            <p>{{ $err }}</p>
        @endforeach
        @endif
      </body>
    </html>