<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Importer des étudiants</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; }
        .success { background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 15px; }
        .error   { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 15px; }
        input[type="file"] { margin: 10px 0; }
        button { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
    </style>
</head>
<body>

    <h2>Importer des étudiants et projets (Excel)</h2>

    {{-- Message de succès --}}
    @if(session('success'))
        <div class="success">{{ session('success') }}</div>
    @endif

    {{-- Erreurs de validation --}}
    @if($errors->any())
        <div class="error">
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('import.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
       
        <label>Choisir un fichier Excel (.xlsx) :</label><br>
        <input type="file" name="file" accept=".xlsx,.xls">
        <br><br>
        <button type="submit">Importer</button>
    </form>

</body>
</html>