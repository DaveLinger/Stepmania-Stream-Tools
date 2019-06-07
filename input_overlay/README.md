## What's all this?

Okay, so what you are looking at here is a "ddrdave" folder with 3 files inside.

This is a "theme" or "profile" for the **[Input Overlay](https://obsproject.com/forum/resources/input-overlay.552/)** plugin for OBS.

### Usage

Unless you are streaming from the PC that is running Stepmania, you need to get the keyboard inputs from your stepmania machine onto your streaming machine, or this won't work. So I use a piece of free software called **[Input Director](https://www.inputdirector.com)** to mirror the keyboard inputs from the Stepmania PC to the streaming PC. There's virtually no latency. Install the software on both PCs, setup your steaming PC as a slave and use the software on your Stepmania PC to "Mirror keyboard input across slaves".

It doesn't matter where you put this folder. Just stick it somewhere and add an Input Overlay source. In the source properties, select arrows.png as the **Overlay image file** and select ddr.ini as the **Layout config file**.

Then, for aesthetics, you can add stage.png as an image source BELOW the input overlay source, to make it look like a real DDR stage instead of floating arrows (optional).

### Note

ddr.ini contains configuration for which keypresses correspond to which arrows presses. This is the config file as **I** use it, which is NOT the default stepmania key bindings. So you may need to adjust this to work with your bindings, or adjust your bindings to match this config.
