function server_cliente(model) {
    return new Promise((resolve, reject) => {
        $.ajax({
            type: "POST",
            // Fixed endpoint to exactly match the refactored controller.
            url: "php/controlador/controller_login.php",
            data: {
                trama: JSON.stringify(model)
            },
            success: function (response) {
                let data = typeof response === "string" ? JSON.parse(response) : response;
                resolve(data);
            },
            error: function (error) {
                console.error("AJAX Error:", error);
                reject(error);
            }
        });
    });
}

// Reuse login call natively
function server_login(model) {
    return new Promise((resolve, reject) => {
        $.ajax({
            type: "POST",
            url: "php/controlador/controller_login.php",
            data: {
                trama: JSON.stringify(model)
            },
            success: function (response) {
                let data = typeof response === "string" ? JSON.parse(response) : response;
                resolve(data);
            },
            error: function (error) {
                reject(error);
            }
        });
    });
}

async function registrar_usuario() {
    let userNameInput = $("#rg-nombre").val() || "";
    let userPassInput = $("#rg-contraseña").val() || "";
    let userEmailInput = $("#rg-correo").val() || "";

    let successMessage = document.getElementById('mensaje-success');
    let errorMessage = document.getElementById('mensaje-danger');

    if (!userNameInput.trim() || !userPassInput.trim() || !userEmailInput.trim()) {
        errorMessage.style.display = 'block';
        errorMessage.textContent = 'Llene todos los campos requeridos.';
        return;
    }

    let model = {
        accion: 1, // Action 1 executes registration based on the refactored PHP code
        nombre: userNameInput.trim(),
        correo: userEmailInput.trim().toLowerCase(),
        contraseña: userPassInput, // Match exactly what PHP script expects (trama->contraseña)
        matricula: "00000" // Adding dummy since original PHP schema required matricula
    };

    try {
        let response = await server_cliente(model);

        if (response.status === 'success') {
            successMessage.style.display = 'block';
            successMessage.textContent = 'Registrado con éxito. Iniciando sesión...';
            errorMessage.style.display = 'none';

            // Auto login after registration
            let loginModel = {
                accion: 0,
                nombre: model.nombre,
                contraseña: model.contraseña,
            };

            let loginResponse = await server_login(loginModel);
            if (loginResponse.status === 'success') {
                sessionStorage.setItem("user_data", JSON.stringify(loginResponse.data));
                sessionStorage.setItem("log", 'true');
                window.location.href = "vista_cliente.html";
            }
        } else {
            errorMessage.style.display = 'block';
            errorMessage.textContent = response.message || 'El correo/usuario ya existe.';
            successMessage.style.display = 'none';
        }
    } catch (e) {
        errorMessage.style.display = 'block';
        errorMessage.textContent = 'Falla de conectividad con el servidor.';
    }
}