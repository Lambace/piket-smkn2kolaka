import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head } from "@inertiajs/react";

export default function Index({
    hari_ini,
    tanggal_sekarang,
    koordinator_hari_ini,
    ringkasan_hari_ini,
    riwayat,
    status_device,
    grup_sekolah,
}) {
    const StatusBadge = ({ status }) => {
        if (status === "online") {
            return (
                <span className="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-700">
                    <span className="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                    Online
                </span>
            );
        }
        return (
            <span className="inline-flex items-center gap-1 rounded-full bg-rose-100 px-2.5 py-0.5 text-xs font-semibold text-rose-700">
                <span className="h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                Offline
            </span>
        );
    };

    const RingkasanCard = ({ judul, data, icon }) => {
        const persen =
            data.total > 0 ? Math.round((data.sukses / data.total) * 100) : 0;
        return (
            <div className="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <div className="mb-3 flex items-center gap-2">
                    <span className="text-2xl">{icon}</span>
                    <h3 className="text-sm font-semibold text-gray-700">
                        {judul}
                    </h3>
                </div>
                <div className="grid grid-cols-3 gap-2 text-center">
                    <div>
                        <div className="text-2xl font-bold text-gray-900">
                            {data.total}
                        </div>
                        <div className="text-xs text-gray-500">Total</div>
                    </div>
                    <div>
                        <div className="text-2xl font-bold text-emerald-600">
                            {data.sukses}
                        </div>
                        <div className="text-xs text-gray-500">Sukses</div>
                    </div>
                    <div>
                        <div className="text-2xl font-bold text-rose-600">
                            {data.gagal}
                        </div>
                        <div className="text-xs text-gray-500">Gagal</div>
                    </div>
                </div>
                {data.total > 0 && (
                    <div className="mt-3">
                        <div className="mb-1 flex justify-between text-xs text-gray-500">
                            <span>Tingkat keberhasilan</span>
                            <span className="font-semibold">{persen}%</span>
                        </div>
                        <div className="h-2 w-full overflow-hidden rounded-full bg-gray-200">
                            <div
                                className={`h-full rounded-full ${persen >= 80 ? "bg-emerald-500" : persen >= 50 ? "bg-amber-500" : "bg-rose-500"}`}
                                style={{ width: `${persen}%` }}
                            ></div>
                        </div>
                    </div>
                )}
            </div>
        );
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    📊 Monitoring Pengiriman
                </h2>
            }
        >
            <Head title="Monitoring Pengiriman" />

            <div className="space-y-6 py-6">
                {/* Info Hari Ini */}
                <div className="rounded-lg bg-gradient-to-r from-indigo-500 to-purple-600 p-5 text-white shadow">
                    <div className="flex items-center justify-between">
                        <div>
                            <div className="text-sm opacity-90">Hari Ini</div>
                            <div className="text-2xl font-bold">{hari_ini}</div>
                            <div className="text-sm opacity-90">
                                {tanggal_sekarang}
                            </div>
                        </div>
                        <div className="text-right">
                            <div className="text-sm opacity-90">
                                Koordinator Bertugas
                            </div>
                            <div className="text-lg font-bold">
                                {koordinator_hari_ini?.nama ?? "— Tidak Ada —"}
                            </div>
                            {koordinator_hari_ini && (
                                <div className="text-sm opacity-90">
                                    Hari Piket:{" "}
                                    {koordinator_hari_ini.hari_piket}
                                </div>
                            )}
                        </div>
                    </div>
                </div>

                {/* Ringkasan Hari Ini */}
                <div className="grid gap-4 md:grid-cols-2">
                    <RingkasanCard
                        judul="Banner ke Grup Sekolah"
                        icon="️"
                        data={ringkasan_hari_ini.banner}
                    />
                    <RingkasanCard
                        judul="Rekap ke Wali Kelas"
                        icon="📨"
                        data={ringkasan_hari_ini.rekap_wali}
                    />
                </div>

                {/* Riwayat 7 Hari */}
                <div className="rounded-lg bg-white p-5 shadow">
                    <h3 className="mb-4 text-lg font-semibold text-gray-800">
                        📅 Riwayat 7 Hari Terakhir
                    </h3>
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b bg-gray-50 text-left text-gray-600">
                                    <th className="p-3">Tanggal</th>
                                    <th className="p-3">Hari</th>
                                    <th className="p-3 text-center">Banner</th>
                                    <th className="p-3 text-center">
                                        Rekap Wali
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {riwayat.map((r) => (
                                    <tr key={r.tanggal} className="border-b">
                                        <td className="p-3 font-mono text-xs">
                                            {r.tanggal}
                                        </td>
                                        <td className="p-3 font-medium">
                                            {r.hari}
                                        </td>
                                        <td className="p-3 text-center">
                                            {r.banner_total === 0 ? (
                                                <span className="text-xs text-gray-400">
                                                    —
                                                </span>
                                            ) : (
                                                <span
                                                    className={`inline-flex items-center gap-1 rounded px-2 py-0.5 text-xs font-semibold ${
                                                        r.banner_sukses ===
                                                        r.banner_total
                                                            ? "bg-emerald-100 text-emerald-700"
                                                            : "bg-rose-100 text-rose-700"
                                                    }`}
                                                >
                                                    {r.banner_sukses}/
                                                    {r.banner_total}
                                                </span>
                                            )}
                                        </td>
                                        <td className="p-3 text-center">
                                            {r.rekap_total === 0 ? (
                                                <span className="text-xs text-gray-400">
                                                    —
                                                </span>
                                            ) : (
                                                <span
                                                    className={`inline-flex items-center gap-1 rounded px-2 py-0.5 text-xs font-semibold ${
                                                        r.rekap_sukses ===
                                                        r.rekap_total
                                                            ? "bg-emerald-100 text-emerald-700"
                                                            : "bg-rose-100 text-rose-700"
                                                    }`}
                                                >
                                                    {r.rekap_sukses}/
                                                    {r.rekap_total}
                                                </span>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>

                {/* Status Device Koordinator */}
                <div className="rounded-lg bg-white p-5 shadow">
                    <div className="mb-4 flex items-center justify-between">
                        <h3 className="text-lg font-semibold text-gray-800">
                            📱 Status Device Fonnte Koordinator
                        </h3>
                        <span className="text-xs text-gray-500">
                            Dicek real-time dari API Fonnte
                        </span>
                    </div>

                    {status_device.length === 0 ? (
                        <div className="rounded bg-amber-50 p-4 text-sm text-amber-700">
                            ⚠️ Belum ada koordinator/petugas yang terdaftar
                            dengan No. WA & Token Fonnte. Tambahkan di menu{" "}
                            <b>Akun Petugas</b>.
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b bg-gray-50 text-left text-gray-600">
                                        <th className="p-3">Nama</th>
                                        <th className="p-3">Role</th>
                                        <th className="p-3">Hari Piket</th>
                                        <th className="p-3">No. WA</th>
                                        <th className="p-3">Status Device</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {status_device.map((d) => (
                                        <tr key={d.id} className="border-b">
                                            <td className="p-3 font-medium">
                                                {d.nama}
                                            </td>
                                            <td className="p-3">
                                                <span
                                                    className={`rounded-full px-2 py-0.5 text-xs font-semibold ${
                                                        d.role === "koordinator"
                                                            ? "bg-indigo-100 text-indigo-700"
                                                            : "bg-gray-100 text-gray-700"
                                                    }`}
                                                >
                                                    {d.role === "koordinator"
                                                        ? "Koordinator"
                                                        : "Petugas"}
                                                </span>
                                            </td>
                                            <td className="p-3">
                                                {d.hari_piket || "—"}
                                            </td>
                                            <td className="p-3 font-mono text-xs">
                                                {d.no_wa}
                                            </td>
                                            <td className="p-3">
                                                <StatusBadge
                                                    status={d.device_status}
                                                />
                                                {d.device_name && (
                                                    <div className="mt-1 text-[10px] text-gray-500">
                                                        {d.device_name}
                                                    </div>
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>

                {/* Info Grup */}
                <div className="rounded-lg bg-gray-50 p-4 text-sm text-gray-600">
                    <b>🎯 Grup Tujuan:</b>{" "}
                    <code className="rounded bg-white px-2 py-0.5 font-mono text-xs">
                        {grup_sekolah}
                    </code>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
