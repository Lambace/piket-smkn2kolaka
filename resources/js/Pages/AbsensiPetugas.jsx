import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head, router, usePage } from "@inertiajs/react";
import { useState } from "react";

export default function AbsensiPetugas(props) {
    const flash = usePage().props.flash;
    const [nama, setNama] = useState("");
    const [jabatan, setJabatan] = useState("");

    const absenSekarang = () =>
        router.post(route("absensi.store"), {
            nama: props.userName,
            jabatan: "Guru Piket",
        });

    const submitManual = (e) => {
        e.preventDefault();
        router.post(route("absensi.store"), { nama, jabatan });
        setNama("");
        setJabatan("");
    };

    const hapus = (id) => {
        if (confirm("Hapus absensi ini?"))
            router.delete(route("absensi.destroy", id));
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Absensi Petugas Piket
                </h2>
            }
        >
            <Head title="Absensi Petugas" />

            <div className="space-y-6">
                {flash?.success && (
                    <div className="rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-800">
                        ✅ {flash.success}
                    </div>
                )}

                <div className="rounded-lg bg-white p-6 shadow">
                    <h3 className="mb-1 text-lg font-bold">
                        📅 {props.hariIni}
                    </h3>
                    <p className="mb-4 text-sm text-gray-500">
                        Absen sebelum 07:00 = ✅ Tepat Waktu • setelah itu = ⏰
                        Terlambat
                    </p>

                    <button
                        onClick={absenSekarang}
                        disabled={props.sudahAbsen}
                        className="rounded-lg bg-indigo-600 px-6 py-3 text-sm font-bold text-white shadow transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:bg-gray-400"
                    >
                        {props.sudahAbsen
                            ? "✅ Anda Sudah Absen Hari Ini"
                            : "🖐️ Absen Sekarang (Sekali Klik)"}
                    </button>

                    <form
                        onSubmit={submitManual}
                        className="mt-6 flex flex-wrap items-end gap-3 border-t pt-5"
                    >
                        <div>
                            <label className="mb-1 block text-xs font-semibold text-gray-600">
                                Input Manual — Nama
                            </label>
                            <input
                                value={nama}
                                onChange={(e) => setNama(e.target.value)}
                                required
                                className="rounded-lg border-gray-300 text-sm"
                                placeholder="Nama petugas"
                            />
                        </div>
                        <div>
                            <label className="mb-1 block text-xs font-semibold text-gray-600">
                                Jabatan
                            </label>
                            <input
                                value={jabatan}
                                onChange={(e) => setJabatan(e.target.value)}
                                className="rounded-lg border-gray-300 text-sm"
                                placeholder="Guru Piket / Kepala Sekolah"
                            />
                        </div>
                        <button className="rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700">
                            + Catat Absen
                        </button>
                    </form>
                </div>

                <div className="rounded-lg bg-white p-6 shadow">
                    <h3 className="mb-4 text-lg font-bold">
                        Daftar Absen Hari Ini
                    </h3>
                    {props.absensiHariIni.length === 0 ? (
                        <p className="text-sm text-gray-500">
                            Belum ada absen hari ini.
                        </p>
                    ) : (
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b text-left text-gray-500">
                                    <th className="py-2">Nama</th>
                                    <th className="py-2">Jabatan</th>
                                    <th className="py-2">Jam</th>
                                    <th className="py-2">Status</th>
                                    <th className="py-2"></th>
                                </tr>
                            </thead>
                            <tbody>
                                {props.absensiHariIni.map((a) => (
                                    <tr key={a.id} className="border-b">
                                        <td className="py-2 font-semibold">
                                            {a.nama}
                                        </td>
                                        <td className="py-2">{a.jabatan}</td>
                                        <td className="py-2">
                                            {a.jam_masuk?.substring(0, 5)}
                                        </td>
                                        <td className="py-2">
                                            {a.status === "tepat_waktu"
                                                ? "✅ Tepat Waktu"
                                                : "⏰ Terlambat"}
                                        </td>
                                        <td className="py-2 text-right">
                                            <button
                                                onClick={() => hapus(a.id)}
                                                className="text-xs text-red-500 hover:underline"
                                            >
                                                Hapus
                                            </button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
