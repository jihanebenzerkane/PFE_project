@extends('layouts.app')
@section('title', 'Historique Affectation')
@section('topbar-actions')
    <a href="{{ route('affectation.index') }}" class="btn btn-outline btn-sm">← Retour</a>
@endsection
@section('content')
<div class="card">
    <div class="card-header">
        <div class="card-title">📋 Historique des Affectations</div>
    </div>
    <table style="width:100%;border-collapse:collapse;">
        <thead>
            <tr>
                <th style="text-align:left;padding:10px 14px;font-size:0.75rem;font-weight:600;color:#64748B;border-bottom:2px solid #F1F5F9;text-transform:uppercase;">Libellé</th>
                <th style="text-align:left;padding:10px 14px;font-size:0.75rem;font-weight:600;color:#64748B;border-bottom:2px solid #F1F5F9;text-transform:uppercase;">Date</th>
                <th style="text-align:left;padding:10px 14px;font-size:0.75rem;font-weight:600;color:#64748B;border-bottom:2px solid #F1F5F9;text-transform:uppercase;">Étudiants</th>
                <th style="text-align:left;padding:10px 14px;font-size:0.75rem;font-weight:600;color:#64748B;border-bottom:2px solid #F1F5F9;text-transform:uppercase;">Télécharger</th>
            </tr>
        </thead>
        <tbody>
            @forelse($snapshots as $snap)
            <tr>
                <td style="padding:12px 14px;font-size:0.875rem;border-bottom:1px solid #F8FAFC;">{{ $snap->label }}</td>
                <td style="padding:12px 14px;font-size:0.875rem;border-bottom:1px solid #F8FAFC;color:#64748B;">{{ $snap->created_at->format('d/m/Y à H:i') }}</td>
                <td style="padding:12px 14px;font-size:0.875rem;border-bottom:1px solid #F8FAFC;">
                    <span style="background:#ECFDF5;color:#065F46;padding:3px 10px;border-radius:999px;font-size:0.75rem;font-weight:600;">{{ $snap->etudiants_count }} étudiants</span>
                </td>
                <td style="padding:12px 14px;border-bottom:1px solid #F8FAFC;">
                    <div style="display:flex;gap:8px;">
                        <a href="{{ route('snapshot.download', ['type'=>'affectation','id'=>$snap->id,'format'=>'pdf']) }}" class="btn btn-danger btn-sm">📄 PDF</a>
                        <a href="{{ route('snapshot.download', ['type'=>'affectation','id'=>$snap->id,'format'=>'word']) }}" class="btn btn-outline btn-sm">📝 Word</a>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="4" style="text-align:center;padding:40px;color:#94A3B8;">Aucun historique disponible.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
