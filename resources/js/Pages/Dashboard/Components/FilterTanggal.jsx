import { router } from "@inertiajs/react";
import { useState } from "react";

export default function FilterTanggal({ params }) {
    const [dari, setDari] = useState(params.dari_tanggal ?? "");
    const [sampai, setSampai] = useState(params.sampai_tanggal ?? "");
    const [periode, setPeriode] = useState(params.periode_grafik ?? "7");

    const apply = () => {
        router.get(
            route("dashboard"),
            {
                dari_tanggal: dari || undefined,
                sampai_tanggal: sampai || undefined,
                periode_grafik: periode,
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    const preset = (type) => {
        const today = new Date();
        let d, s;
        if (type === "today") {
            d = s = today.toISOString().split("T")[0];
        } else if (type === "week") {
            const week = new Date(today);
            week.setDate(today.getDate() - 7);
            d = week.toISOString().split("T")[0];
            s = today.toISOString().split("T")[0];
        } else if (type === "month") {
            d = new Date(today.getFullYear(), today.getMonth(), 1)
                .toISOString()
                .split("T")[0];
            s = today.toISOString().split("T")[0];
        }
        setDari(d);
        setSampai(s);
        router.get(
            route("dashboard"),
            {
                dari_tanggal: d,
                sampai_tanggal: s,
                periode_grafik: periode,
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    return (
        <div className="rounded-lg bg-white p-4 shadow">
            <div className="flex flex-wrap items-end gap-3">
                <div>
                    <label className="text-xs font-medium text-gray-600">
                        Dari Tanggal
                    </label>
                    <input
                        type="date"
                        value={dari}
                        onChange={(e) => setDari(e.target.value)}
                        className="mt-1 block rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                </div>
                <div>
                    <label className="text-xs font-medium text-gray-600">
                        Sampai Tanggal
                    </label>
                    <input
                        type="date"
                        value={sampai}
                        onChange={(e) => setSampai(e.target.value)}
                        className="mt-1 block rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                </div>
                <div>
                    <label className="text-xs font-medium text-gray-600">
                        Periode Grafik
                    </label>
                    <select
                        value={periode}
                        onChange={(e) => setPeriode(e.target.value)}
                        className="mt-1 block rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option value="7">7 Hari Terakhir</option>
                        <option value="14">14 Hari Terakhir</option>
                        <option value="30">30 Hari Terakhir</option>
                    </select>
                </div>
                <button
                    onClick={apply}
                    className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700"
                >
                    Terapkan
                </button>
                <div className="ml-auto flex gap-2">
                    <button
                        onClick={() => preset("today")}
                        className="rounded-md bg-gray-100 px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-200"
                    >
                        Hari Ini
                    </button>
                    <button
                        onClick={() => preset("week")}
                        className="rounded-md bg-gray-100 px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-200"
                    >
                        7 Hari
                    </button>
                    <button
                        onClick={() => preset("month")}
                        className="rounded-md bg-gray-100 px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-200"
                    >
                        Bulan Ini
                    </button>
                </div>
            </div>
        </div>
    );
}
