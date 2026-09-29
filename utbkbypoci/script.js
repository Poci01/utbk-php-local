// Soal Tryout (contoh awal)
const questions = [
    {
        question: "Ibu kota Indonesia adalah?",
        options: ["Jakarta", "Bandung", "Surabaya", "Medan"],
        answer: 0
    },
    {
        question: "2 + 2 × 3 = ?",
        options: ["12", "8", "10", "6"],
        answer: 1
    },
    {
        question: "Siapa penemu lampu pijar?",
        options: ["Albert Einstein", "Thomas Edison", "Isaac Newton", "Nikola Tesla"],
        answer: 1
    }
];

let currentQuestionIndex = 0;
let userAnswers = [];
let timeLimit = 60; // detik
let timerInterval;

const questionContainer = document.getElementById("question-container");
const timerDisplay = document.getElementById("timer");
const prevBtn = document.getElementById("prev-btn");
const nextBtn = document.getElementById("next-btn");
const submitBtn = document.getElementById("submit-btn");
const resultDisplay = document.getElementById("result");

function startTimer() {
    let timeLeft = timeLimit;
    timerInterval = setInterval(() => {
        let minutes = Math.floor(timeLeft / 60);
        let seconds = timeLeft % 60;
        timerDisplay.textContent = `Waktu: ${minutes}:${seconds < 10 ? '0' : ''}${seconds}`;
        timeLeft--;

        if (timeLeft < 0) {
            clearInterval(timerInterval);
            submitQuiz();
        }
    }, 1000);
}

function showQuestion(index) {
    const q = questions[index];
    questionContainer.innerHTML = `
        <h3>${index + 1}. ${q.question}</h3>
        ${q.options.map((opt, i) => `
            <div>
                <input type="radio" name="answer" value="${i}" ${userAnswers[index] === i ? 'checked' : ''}>
                ${opt}
            </div>
        `).join("")}
    `;
}

function saveAnswer() {
    const selected = document.querySelector('input[name="answer"]:checked');
    if (selected) {
        userAnswers[currentQuestionIndex] = parseInt(selected.value);
    }
}

prevBtn.addEventListener("click", () => {
    saveAnswer();
    if (currentQuestionIndex > 0) {
        currentQuestionIndex--;
        showQuestion(currentQuestionIndex);
    }
});

nextBtn.addEventListener("click", () => {
    saveAnswer();
    if (currentQuestionIndex < questions.length - 1) {
        currentQuestionIndex++;
        showQuestion(currentQuestionIndex);
    }
});

submitBtn.addEventListener("click", () => {
    saveAnswer();
    submitQuiz();
});

function submitQuiz() {
    clearInterval(timerInterval);
    let score = 0;
    questions.forEach((q, i) => {
        if (userAnswers[i] === q.answer) {
            score++;
        }
    });
    resultDisplay.textContent = `Skor Kamu: ${score} dari ${questions.length}`;
    questionContainer.innerHTML = "";
    prevBtn.style.display = "none";
    nextBtn.style.display = "none";
    submitBtn.style.display = "none";
}

// Mulai
showQuestion(currentQuestionIndex);
startTimer();
