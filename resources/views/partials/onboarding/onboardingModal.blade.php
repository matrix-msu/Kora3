<div class="modal modal-js modal-mask onboarding-modal onboarding-modal-js">
    <div class="content">
        <section id="onboarding-home">
            <div class="header onboarding">
                <span class="title">
                    <span class="skip modal-toggle-js">
                        <span>SKIP INTRO</span>
                        <a href="#" class="modal-toggle">
                            <i class="icon icon-cancel"></i>
                        </a>
                    </span>
                </span>
                <img src="{{ url('/assets/images/onboarding/welcome.png') }}" alt="Onboarding balloons picture">
            </div>
            <div class="body onboarding">
                <h3>You're in, <span>{{ trim(Auth::User()->first_name) }}!</span> Welcome to Kora! 🎉</h3>
                <p>Welcome to Kora, the easiest way to manage and publish your data. Before we get started, <span class="bold">are you new to kora?</span> If you are, we’d love to teach you the basics of how Kora is structured! If you’re an experienced Kora user, we’re going to assume you understand the basics, and let you loose into the Kora wild!</p>
                <div class="form-group mt-xxl">
                    <a class="btn half-sub-btn not-new-js">I have used Kora before!</a>
                    <a class="btn half-btn right new-to-kora-js">I am new to Kora!</a>
                </div>
            </div>
        </section>

		<div class="paths {{ \Auth::user()->admin ? null : 'non-admin-js' }}">

			@if (\Auth::user()->admin)
				@include('partials.onboarding.familiarWithKora-admin')
				@include('partials.onboarding.newToKora-admin')
			@else
				@include('partials.onboarding.familiarWithKora')
				@include('partials.onboarding.newToKora')
			@endif

		</div>

		<div class="onboarding-pagination onboarding-pagination-js hidden mobile">
			<div class="next next-js mobile">
				<span>Continue</span>
				<a><i class="icon icon-arrow-right"></i></a>
			</div>
			<div class="next finish finish-js mobile">
				<span>Finish</span>
			</div>
		</div>
		<div class="onboarding-pagination onboarding-pagination-js hidden">
			<div class="prev prev-js">
				<a><i class="icon icon-arrow-left"></i></a>
				<span>Previous</span>
			</div>
			<div class="dots"></div>
			<div class="next next-js">
				<span>Continue</span>
				<a><i class="icon icon-arrow-right"></i></a>
			</div>
			<div class="next finish finish-js ">
				<span>Finish</span>
			</div>
		</div>
    </div>
</div>