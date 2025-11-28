// Gráfico de categorías con habitaciones
const ctx1 = document.getElementById("chartHabitaciones");
new Chart(ctx1, {
  type: "pie",
  data: {
    labels: ["Simple", "Doble"],
    datasets: [
      {
        data: [25, 75],
        backgroundColor: ["#3b82f6", "#ef4444"],
      },
    ],
  },
  options: {
    plugins: {
      legend: {
        position: "right",
        labels: { color: "#fff" },
      },
    },
  },
});

// Gráfico de personas registradas
const ctx2 = document.getElementById("chartPersonas");
new Chart(ctx2, {
  type: "pie",
  data: {
    labels: ["Clientes", "Empleados", "Administradores"],
    datasets: [
      {
        data: [60, 25, 15],
        backgroundColor: ["#3b82f6", "#10b981", "#f59e0b"],
      },
    ],
  },
  options: {
    plugins: {
      legend: {
        position: "right",
        labels: { color: "#fff" },
      },
    },
  },
});


// Simulación de datos (reemplazar por tu backend)
const pagos = [
    { fecha: "2024-09-10", reserva: 5, cliente: "Ana Hernández", cedula: "890123456", monto: 200, estado: "completado" },
    { fecha: "2024-09-07", reserva: 3, cliente: "Giselle Aguilera Frias", cedula: "7720233", monto: 440, estado: "completado" },
    { fecha: "2024-09-07", reserva: 3, cliente: "Giselle Aguilera Frias", cedula: "7720233", monto: 440, estado: "cancelado" },
    { fecha: "2024-08-14", reserva: 2, cliente: "User Prueba", cedula: "99999999", monto: 400, estado: "completado" },
];

// Cargar tabla
function cargarTabla(data) {
    const tbody = document.getElementById("tablaPagos");
    tbody.innerHTML = "";

    data.forEach(p => {
        const tr = document.createElement("tr");

        tr.innerHTML = `
            <td>${p.fecha}</td>
            <td>${p.reserva}</td>
            <td>${p.cliente}</td>
            <td>${p.cedula}</td>
            <td>${p.monto}</td>
            <td><span class="label ${p.estado}">${p.estado}</span></td>
            <td>
                <button class="btn yellow">PDF</button>
                <button class="btn red">Cancelar</button>
            </td>
        `;

        tbody.appendChild(tr);
    });
}

cargarTabla(pagos);

// Botón BUSCAR
function buscar() {
    let estado = document.getElementById("estado").value;
    let texto = document.getElementById("busqueda").value.toLowerCase();

    let filtrado = pagos.filter(p =>
        (estado === "" || p.estado === estado) &&
        (texto === "" || p.cliente.toLowerCase().includes(texto))
    );

    cargarTabla(filtrado);
}

// Botón LIMPIAR
function limpiar() {
    document.getElementById("estado").value = "";
    document.getElementById("busqueda").value = "";
    document.getElementById("fecha_inicio").value = "";
    document.getElementById("fecha_fin").value = "";

    cargarTabla(pagos);
}
