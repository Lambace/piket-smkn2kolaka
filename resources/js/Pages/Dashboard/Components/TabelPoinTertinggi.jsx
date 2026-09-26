// Di dalam return komponen TabelPoinTertinggi:
<div className="rounded-lg bg-white p-4 sm:p-6 shadow w-full">
    <h3 className="mb-4 text-base font-semibold text-gray-800">
        ⚠️ Poin Pelanggaran Tertinggi
    </h3>
    {data.length === 0 ? (
        <p className="py-8 text-center text-sm text-gray-400">
            Belum ada data pelanggaran 🎉
        </p>
    ) : (
        // ✅ TERAPKAN POLA INI DI SEMUA TABEL
        <div className="w-full overflow-x-auto -mx-4 sm:-mx-6 px-4 sm:px-6">
            <table className="w-full text-sm min-w-[500px]">
                {/* ... isi tabel ... */}
            </table>
        </div>
    )}
</div>;
