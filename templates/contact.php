<?php $pageTitle = 'Contact Us'; ?>

<h1>Contact Us</h1>

<p>This form is powered by <strong>Formspree</strong>. It requires no backend code on your server.</p>

<!-- 
    1. Go to https://formspree.io/ and create a free account.
    2. Create a new form.
    3. Replace 'YOUR_FORM_ID' in the action URL below with the ID they give you.
-->
<form action="https://formspree.io/f/YOUR_FORM_ID" method="POST" class="contact-form">
    <div class="form-group">
        <label for="email">Your Email</label>
        <input type="email" id="email" name="email" required placeholder="you@example.com">
    </div>

    <div class="form-group">
        <label for="message">Message</label>
        <textarea id="message" name="message" required rows="5" placeholder="How can we help?"></textarea>
    </div>

    <button type="submit">Send Message</button>
</form>

<style>
    /* Simple form styles scoped to this page */
    .contact-form {
        max-width: 500px;
        margin-top: 2rem;
    }
    .form-group {
        margin-bottom: 1.5rem;
    }
    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 500;
        color: #333;
    }
    .form-group input,
    .form-group textarea {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-family: inherit;
        font-size: 1rem;
    }
    .form-group input:focus,
    .form-group textarea:focus {
        border-color: #0066cc;
        outline: none;
        box-shadow: 0 0 0 2px rgba(0,102,204,0.1);
    }
    button[type="submit"] {
        background-color: #0066cc;
        color: white;
        border: none;
        padding: 0.75rem 1.5rem;
        font-size: 1rem;
        border-radius: 4px;
        cursor: pointer;
        transition: background-color 0.2s;
    }
    button[type="submit"]:hover {
        background-color: #0052a3;
    }
</style>
